<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderDelivered;
use Coderstm\Notifications\Shop\OrderDeliveredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderDeliveredNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(OrderDelivered $event)
    {
        $order = $event->order;
        if ($order->customer) {
            $order->customer->notify(new OrderDeliveredNotification($order));
        }
    }
}
