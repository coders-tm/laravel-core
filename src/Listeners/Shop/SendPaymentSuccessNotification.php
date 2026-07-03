<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PaymentSuccessful;
use Coderstm\Notifications\Shop\PaymentSuccessNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSuccessNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(PaymentSuccessful $event)
    {
        $order = $event->order;

        // Send notification to customer
        if ($order->customer) {
            $order->customer->notify(new PaymentSuccessNotification($order));
        }
    }
}
