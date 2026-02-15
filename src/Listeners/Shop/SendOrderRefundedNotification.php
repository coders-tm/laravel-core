<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderRefunded;
use Coderstm\Notifications\Shop\OrderRefundedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderRefundedNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(OrderRefunded $event)
    {
        $order = $event->order;
        $payment = $event->payment;
        $amount = $event->amount;
        $reason = $event->reason;
        if ($order->customer) {
            $order->customer->notify(new OrderRefundedNotification($order, $payment, $amount, $reason));
        }
    }
}
