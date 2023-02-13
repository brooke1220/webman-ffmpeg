<?php

namespace Brooke1220\WebmanFfmpeg\Support;

use support\Facade;

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
class FFMpeg extends Facade
{
    protected static function getFacadeClass()
    {
        return 'webman-ffmpeg';
    }
}
