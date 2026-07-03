<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Notifications\Shop\Admins\RefundProcessedNotification;

class SendAdminRefundNotification extends AdminNotificationListener
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event  OrderRefunded|PartialRefundProcessed
     */
    public function handle($event): void
    {
        $this->notifyForEvent(fn ($admin) => new RefundProcessedNotification(
            $event->order,
            $event->payment,
            $event->amount,
            $event->reason
        ));
    }
}
