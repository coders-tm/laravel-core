<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionUpgraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public $plan;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
        $this->plan = $subscription->oldPlan;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
