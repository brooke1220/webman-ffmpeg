<?php

namespace Brooke1220\WebmanFfmpeg\Exporters;

use Illuminate\Support\Collection;
use Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg;
use Brooke1220\WebmanFfmpeg\Filesystem\Media;
use Brooke1220\WebmanFfmpeg\Http\DynamicHLSPlaylist;
use Brooke1220\WebmanFfmpeg\MediaOpener;
use Brooke1220\WebmanFfmpeg\Support\StreamParser;

class HLSPlaylistGenerator implements PlaylistGenerator
{
    public const PLAYLIST_START = '#EXTM3U';
    public const PLAYLIST_END   = '#EXT-X-ENDLIST';

    /**
     * Return the line from the master playlist that references the given segment playlist.
     *
     * @param \Brooke1220\WebmanFfmpeg\Filesystem\Media $playlistMedia
     * @param string $key
     * @return string
     */
    private function getStreamInfoLine(Media $segmentPlaylistMedia, string $key): string
    {
        $segmentPlaylist = $segmentPlaylistMedia->getDisk()->get(
            $segmentPlaylistMedia->getDirectory() . HLSExporter::generateTemporarySegmentPlaylistFilename($key)
        );

        $lines = DynamicHLSPlaylist::parseLines($segmentPlaylist)->filter();

        return $lines->get($lines->search($segmentPlaylistMedia->getFilename()) - 1);
    }

    /**
     * Loops through all segment playlists and generates a main playlist. It finds
     * the relative paths to the segment playlists and adds the framerate when
     * to each playlist.
     *
     * @param array $segmentPlaylists
     * @param \Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg $driver
     * @return string
     */
    public function get(array $segmentPlaylists, PHPFFMpeg $driver): string
    {
        return Collection::make($segmentPlaylists)->map(function (Media $segmentPlaylist, $key) use ($driver) {
            $streamInfoLine = $this->getStreamInfoLine($segmentPlaylist, $key);

            $media = (new MediaOpener($segmentPlaylist->getDisk(), $driver))
                ->openWithInputOptions($segmentPlaylist->getPath(), ['-allowed_extensions', 'ALL']);

            if ($media->getVideoStream()) {
                if ($frameRate = StreamParser::new($media->getVideoStream())->getFrameRate()) {
                    $streamInfoLine .= ",FRAME-RATE={$frameRate}";
                }
            }

            return [$streamInfoLine, $segmentPlaylist->getFilename()];
        })->collapse()
            ->prepend(static::PLAYLIST_START)
            ->push(static::PLAYLIST_END)
            ->implode(PHP_EOL);
    }
}
