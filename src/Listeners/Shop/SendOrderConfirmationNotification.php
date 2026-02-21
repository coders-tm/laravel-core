<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderPaid;
use Coderstm\Notifications\Shop\OrderConfirmationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderConfirmationNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(OrderPaid $event)
    {
        $order = $event->order;
        if ($order->customer) {
            $order->customer->notify(new OrderConfirmationNotification($order));
        }
    }
}
