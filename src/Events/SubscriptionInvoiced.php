<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public $invoice;

    public function __construct($subscription, $invoice)
    {
        $this->subscription = $subscription;
        $this->invoice = $invoice;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
