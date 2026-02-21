<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PaymentSuccessful;
use Coderstm\Notifications\Shop\PaymentSuccessNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSuccessNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(PaymentSuccessful $event)
    {
        $order = $event->order;
        if ($order->customer) {
            $order->customer->notify(new PaymentSuccessNotification($order));
        }
    }
}
