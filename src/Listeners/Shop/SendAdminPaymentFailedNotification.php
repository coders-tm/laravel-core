<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\PaymentFailed;
use Coderstm\Notifications\Shop\Admins\PaymentFailedNotification;

class SendAdminPaymentFailedNotification extends AdminNotificationListener
{
    public function handle(PaymentFailed $event): void
    {
        $this->notifyForEvent(fn ($admin) => new PaymentFailedNotification($event->order, $event->reason));
    }
}
