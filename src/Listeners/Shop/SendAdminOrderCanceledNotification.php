<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderCanceled;
use Coderstm\Notifications\Shop\Admins\OrderCanceledNotification;

class SendAdminOrderCanceledNotification extends AdminNotificationListener
{
    public function handle(OrderCanceled $event): void
    {
        $this->notifyForEvent(fn ($admin) => new OrderCanceledNotification($event->order));
    }
}
