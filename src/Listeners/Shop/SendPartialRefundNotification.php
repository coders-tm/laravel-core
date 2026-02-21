<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PartialRefundProcessed;
use Coderstm\Notifications\Shop\PartialRefundNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPartialRefundNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(PartialRefundProcessed $event)
    {
        $order = $event->order;
        $payment = $event->payment;
        $amount = $event->amount;
        $reason = $event->reason;
        if ($order->customer) {
            $order->customer->notify(new PartialRefundNotification($order, $payment, $amount, $reason));
        }
    }
}
