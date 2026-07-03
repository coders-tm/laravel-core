<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PartialRefundProcessed;
use Coderstm\Notifications\Shop\PartialRefundNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPartialRefundNotification implements ShouldQueue
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
    public function handle(PartialRefundProcessed $event)
    {
        $order = $event->order;
        $payment = $event->payment;
        $amount = $event->amount;
        $reason = $event->reason;

        // Send notification to customer
        if ($order->customer) {
            $order->customer->notify(new PartialRefundNotification($order, $payment, $amount, $reason));
        }
    }
}
