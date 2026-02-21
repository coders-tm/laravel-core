<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancel
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
