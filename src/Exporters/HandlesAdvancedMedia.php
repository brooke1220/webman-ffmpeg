<?php

namespace Brooke1220\WebmanFfmpeg\Exporters;

use FFMpeg\Format\FormatInterface;
use Brooke1220\WebmanFfmpeg\FFMpeg\AdvancedOutputMapping;
use Brooke1220\WebmanFfmpeg\Filesystem\Media;

trait HandlesAdvancedMedia
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $maps;

    public function addFormatOutputMapping(FormatInterface $format, Media $output, array $outs, $forceDisableAudio = false, $forceDisableVideo = false)
    {
        $this->maps->push(
            new AdvancedOutputMapping($outs, $format, $output, $forceDisableAudio, $forceDisableVideo)
        );

        return $this;
    }
}
