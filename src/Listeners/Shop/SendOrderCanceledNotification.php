<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderCanceled;
use Coderstm\Notifications\Shop\OrderCanceledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderCanceledNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(OrderCanceled $event)
    {
        $order = $event->order;
        if ($order->customer) {
            $order->customer->notify(new OrderCanceledNotification($order));
        }
    }
}
