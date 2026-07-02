<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Notifications\Shop\Admins\RefundProcessedNotification;

class SendAdminRefundNotification extends AdminNotificationListener
{
    public function handle($event): void
    {
        $this->notifyForEvent(fn ($admin) => new RefundProcessedNotification($event->order, $event->payment, $event->amount, $event->reason));
    }
}
