<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ResetFeatureUsages
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $features;
    public $subscription;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(mixed $subscription, mixed $features)
    {
        $this->subscription = $subscription;
        $this->features = $features;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
