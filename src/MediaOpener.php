<?php

namespace Brooke1220\WebmanFfmpeg;

use Brooke1220\WebmanFfmpeg\Support\Container;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Media\AbstractMediaType;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg;
use Brooke1220\WebmanFfmpeg\Exporters\HLSExporter;
use Brooke1220\WebmanFfmpeg\Exporters\MediaExporter;
use Brooke1220\WebmanFfmpeg\FFMpeg\ImageFormat;
use Brooke1220\WebmanFfmpeg\Filesystem\Disk;
use Brooke1220\WebmanFfmpeg\Filesystem\Media;
use Brooke1220\WebmanFfmpeg\Filesystem\MediaCollection;
use Brooke1220\WebmanFfmpeg\Filesystem\MediaOnNetwork;
use Brooke1220\WebmanFfmpeg\Filesystem\TemporaryDirectories;
use Brooke1220\WebmanFfmpeg\Filters\TileFactory;
use Webman\Config;
use Webman\Http\UploadFile;
use WebmanTech\LaravelFilesystem\FilesystemManager;

/**
 * @mixin \Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg
 */
class MediaOpener
{
    use ForwardsCalls;

    /**
     * @var \Brooke1220\WebmanFfmpeg\Filesystem\Disk
     */
    private $disk;

    /**
     * @var \Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg
     */
    private $driver;

    /**
     * @var \Brooke1220\WebmanFfmpeg\Filesystem\MediaCollection
     */
    private $collection;

    /**
     * @var \FFMpeg\Coordinate\TimeCode
     */
    private $timecode;

    /**
     * Uses the 'filesystems.default' disk from the config if none is given.
     * Gets the underlying PHPFFMpeg instance from the container if none is given.
     * Instantiates a fresh MediaCollection if none is given.
     */
    public function __construct($disk = null, PHPFFMpeg $driver = null, MediaCollection $mediaCollection = null)
    {
        $this->fromDisk($disk ?: Config::get('plugin.webman-tech.laravel-filesystem.filesystems.default'));

        $this->driver = ($driver ?: Container::get(PHPFFMpeg::class))->fresh();

        $this->collection = $mediaCollection ?: new MediaCollection();
    }

    public function clone(): self
    {
        return new MediaOpener(
            $this->disk,
            $this->driver,
            $this->collection
        );
    }

    /**
     * Set the disk to open files from.
     */
    public function fromDisk($disk): self
    {
        $this->disk = Disk::make($disk);

        return $this;
    }

    /**
     * Alias for 'fromDisk', mostly for backwards compatibility.
     */
    public function fromFilesystem(Filesystem $filesystem): self
    {
        return $this->fromDisk($filesystem);
    }

    private static function makeLocalDiskFromPath(string $path): Disk
    {
        $adapter = Container::get(FilesystemManager::class)->createLocalDriver([
            'root' => $path,
        ]);

        return Disk::make($adapter);
    }

    /**
     * Instantiates a Media object for each given path.
     */
    public function open($paths): self
    {
        foreach (Arr::wrap($paths) as $path) {
            if ($path instanceof UploadFile) {
                $disk = static::makeLocalDiskFromPath($path->getPath());

                $media = Media::make($disk, $path->getFilename());
            } else {
                $media = Media::make($this->disk, $path);
            }

            $this->collection->push($media);
        }

        return $this;
    }

    /**
     * Instantiates a single Media object and sets the given options on the object.
     *
     * @param string $path
     * @param array $options
     * @return self
     */
    public function openWithInputOptions(string $path, array $options = []): self
    {
        $this->collection->push(
            Media::make($this->disk, $path)->setInputOptions($options)
        );

        return $this;
    }

    /**
     * Instantiates a MediaOnNetwork object for each given url.
     */
    public function openUrl($paths, array $headers = []): self
    {
        foreach (Arr::wrap($paths) as $path) {
            $this->collection->push(MediaOnNetwork::make($path, $headers));
        }

        return $this;
    }

    public function get(): MediaCollection
    {
        return $this->collection;
    }

    public function getDriver(): PHPFFMpeg
    {
        return $this->driver->open($this->collection);
    }

    /**
     * Forces the driver to open the collection with the `openAdvanced` method.
     */
    public function getAdvancedDriver(): PHPFFMpeg
    {
        return $this->driver->openAdvanced($this->collection);
    }

    /**
     * Shortcut to set the timecode by string.
     */
    public function getFrameFromString(string $timecode): self
    {
        return $this->getFrameFromTimecode(TimeCode::fromString($timecode));
    }

    /**
     * Shortcut to set the timecode by seconds.
     */
    public function getFrameFromSeconds(float $seconds): self
    {
        return $this->getFrameFromTimecode(TimeCode::fromSeconds($seconds));
    }

    public function getFrameFromTimecode(TimeCode $timecode): self
    {
        $this->timecode = $timecode;

        return $this;
    }

    /**
     * Returns an instance of MediaExporter with the driver and timecode (if set).
     */
    public function export(): MediaExporter
    {
        return tap(new MediaExporter($this->getDriver()), function (MediaExporter $mediaExporter) {
            if ($this->timecode) {
                $mediaExporter->frame($this->timecode);
            }
        });
    }

    /**
     * Returns an instance of HLSExporter with the driver forced to AdvancedMedia.
     */
    public function exportForHLS(): HLSExporter
    {
        return new HLSExporter($this->getAdvancedDriver());
    }

    /**
     * Returns an instance of MediaExporter with a TileFilter and ImageFormat.
     */
    public function exportTile(callable $withTileFactory): MediaExporter
    {
        return $this->export()
            ->addTileFilter($withTileFactory)
            ->inFormat(new ImageFormat());
    }

    public function exportFramesByAmount(int $amount, int $width = null, int $height = null, int $quality = null): MediaExporter
    {
        $interval = ($this->getDurationInSeconds() + 1) / $amount;

        return $this->exportFramesByInterval($interval, $width, $height, $quality);
    }

    public function exportFramesByInterval(float $interval, int $width = null, int $height = null, int $quality = null): MediaExporter
    {
        return $this->exportTile(
            fn (TileFactory $tileFactory) => $tileFactory
                ->interval($interval)
                ->grid(1, 1)
                ->scale($width, $height)
                ->quality($quality)
        );
    }

    public function cleanupTemporaryFiles(): self
    {
        Container::get(TemporaryDirectories::class)->deleteAll();

        return $this;
    }

    public function each($items, callable $callback): self
    {
        Collection::make($items)->each(function ($item, $key) use ($callback) {
            return $callback($this->clone(), $item, $key);
        });

        return $this;
    }

    /**
     * Returns the Media object from the driver.
     */
    public function __invoke(): AbstractMediaType
    {
        return $this->getDriver()->get();
    }

    /**
     * Forwards all calls to the underlying driver.
     * @return void
     */
    public function __call($method, $arguments)
    {
        $result = $this->forwardCallTo($driver = $this->getDriver(), $method, $arguments);

        return ($result === $driver) ? $this : $result;
    }
}
