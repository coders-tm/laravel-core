<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PaymentFailed;
use Coderstm\Notifications\Shop\PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentFailedNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(PaymentFailed $event)
    {
        $order = $event->order;
        $reason = $event->reason;
        if ($order->customer) {
            $order->customer->notify(new PaymentFailedNotification($order, $reason));
        }
    }
}
