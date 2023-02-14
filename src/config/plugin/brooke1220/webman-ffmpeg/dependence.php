<?php

use Brooke1220\WebmanFfmpeg\Drivers\PHPFFMpeg;
use Brooke1220\WebmanFfmpeg\Filesystem\TemporaryDirectories;
use Brooke1220\WebmanFfmpeg\Support\MediaOpenerFactory;
use support\Log;
use Webman\Config;
use FFMpeg\FFProbe;
use FFMpeg\Driver\FFMpegDriver;
use FFMpeg\FFMpeg;

return [
    'webman-ffmpeg-logger' => function () {
        $logChannel = Config::get('plugin.brooke1220.webman-ffmpeg.log_channel');

        if ($logChannel === false) {
            return null;
        }

        return Log::channel($logChannel ?: 'default');
    },

    'webman-ffmpeg-configuration' => function () {
        $baseConfig = [
            'ffmpeg.binaries'  => Config::get('plugin.brooke1220.webman-ffmpeg.ffmpeg.ffmpeg.binaries'),
            'ffprobe.binaries' => Config::get('plugin.brooke1220.webman-ffmpeg.ffmpeg.ffprobe.binaries'),
            'timeout'          => Config::get('plugin.brooke1220.webman-ffmpeg.ffmpeg.timeout'),
        ];

        $configuredThreads = Config::get('plugin.brooke1220.webman-ffmpeg.ffmpeg.threads', 12);

        if ($configuredThreads !== false) {
            $baseConfig['ffmpeg.threads'] = $configuredThreads;
        }

        if ($configuredTemporaryRoot = Config::get('plugin.brooke1220.webman-ffmpeg.temporary_files_root')) {
            $baseConfig['temporary_directory'] = $configuredTemporaryRoot;
        }

        return $baseConfig;
    },

    FFProbe::class  => function ($container) {
        return FFProbe::create(
            $container->get('webman-ffmpeg-configuration'),
            $container->get('webman-ffmpeg-logger')
        );
    },

    FFMpegDriver::class => function ($container) {
        return FFMpegDriver::create(
            $container->get('webman-ffmpeg-logger'),
            $container->get('webman-ffmpeg-configuration')
        );
    },

    FFMpeg::class => function ($container) {
        return new FFMpeg(
            $container->get(FFMpegDriver::class),
            $container->get(FFProbe::class)
        );
    },

    PHPFFMpeg::class => function ($container) {
        return new PHPFFMpeg($container->get(FFMpeg::class));
    },

    TemporaryDirectories::class => function () {
        return new TemporaryDirectories(
            Config::get('plugin.brooke1220.webman-ffmpeg.temporary_files_root', sys_get_temp_dir()),
        );
    },

    'webman-ffmpeg' => function ($container) {
        return new MediaOpenerFactory(
            Config::get('plugin.webman-tech.laravel-filesystem.filesystems.default'),
            null,
            fn () => $container->get(PHPFFMpeg::class)
        );
    }
];