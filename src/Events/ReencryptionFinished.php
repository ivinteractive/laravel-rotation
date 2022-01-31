<?php

namespace IvInteractive\Rotation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReencryptionFinished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $batchData = [];

    public function __construct(array $batchData)
    {
        $this->batchData = $batchData;
    }
}
