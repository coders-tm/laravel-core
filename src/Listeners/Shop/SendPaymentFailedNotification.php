<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PaymentFailed;
use Coderstm\Notifications\Shop\PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentFailedNotification implements ShouldQueue
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
    public function handle(PaymentFailed $event)
    {
        $order = $event->order;
        $reason = $event->reason;

        // Send notification to customer
        if ($order->customer) {
            $order->customer->notify(new PaymentFailedNotification($order, $reason));
        }
    }
}
