<?php

namespace Brooke1220\WebmanFfmpeg\FFMpeg;

use FFMpeg\Format\Audio\DefaultAudio;

class CopyFormat extends DefaultAudio
{
    public function __construct()
    {
        $this->audioCodec = 'copy';

        $this->audioKiloBitrate = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParams()
    {
        return ['-codec', 'copy'];
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableAudioCodecs()
    {
        return ['copy'];
    }
}
