<?php

namespace Brooke1220\WebmanFfmpeg\Support;

/**
 * @method static \Brooke1220\WebmanFfmpeg\Http\DynamicHLSPlaylist dynamicHLSPlaylist($disk)
 * @method static \Brooke1220\WebmanFfmpeg\MediaOpener fromDisk($disk)
 * @method static \Brooke1220\WebmanFfmpeg\MediaOpener fromFilesystem(\Illuminate\Contracts\Filesystem\Filesystem $filesystem)
 * @method static \Brooke1220\WebmanFfmpeg\MediaOpener open($path)
 * @method static \Brooke1220\WebmanFfmpeg\MediaOpener openUrl($path, array $headers = [])
 * @method static \Brooke1220\WebmanFfmpeg\MediaOpener cleanupTemporaryFiles()
 *
 * @see \Brooke1220\WebmanFfmpeg\MediaOpener
 */
class FFMpeg
{
    public static function instance(): MediaOpenerFactory
    {
        return Container::get('webman-ffmpeg');
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}
