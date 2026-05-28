<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderShipped;
use Coderstm\Notifications\Shop\OrderShippedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderShippedNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(OrderShipped $event)
    {
        $order = $event->order;
        if ($order->customer) {
            $order->customer->notify(new OrderShippedNotification($order));
        }
    }
}
