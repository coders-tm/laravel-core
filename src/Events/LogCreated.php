<?php

namespace Coderstm\Events;

use Coderstm\Models\Log;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
