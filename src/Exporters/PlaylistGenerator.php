<?php

namespace Brooke1220\WebmanFfmpeg\Exporters;

use Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg;

interface PlaylistGenerator
{
    public function get(array $playlistMedia, PHPFFMpeg $driver): string;
}
