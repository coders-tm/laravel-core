<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderRefunded;
use Coderstm\Notifications\Shop\OrderRefundedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderRefundedNotification implements ShouldQueue
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
    public function handle(OrderRefunded $event)
    {
        $order = $event->order;
        $payment = $event->payment;
        $amount = $event->amount;
        $reason = $event->reason;

        // Send notification to customer
        if ($order->customer) {
            $order->customer->notify(new OrderRefundedNotification($order, $payment, $amount, $reason));
        }
    }
}
