<?php

namespace IvInteractive\Rotation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReencryptionFinished
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $batchData = [];

    public function __construct(array $batchData)
    {
        $this->batchData = $batchData;
    }
}
