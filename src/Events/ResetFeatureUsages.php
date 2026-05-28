<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResetFeatureUsages
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $features;

    public $subscription;

    public function __construct(mixed $subscription, mixed $features)
    {
        $this->subscription = $subscription;
        $this->features = $features;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
