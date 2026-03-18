<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OrderPaid;
use Coderstm\Notifications\Shop\Admins\NewOrderNotification;

class SendAdminNewOrderNotification extends AdminNotificationListener
{
    public function handle(OrderPaid $event): void
    {
        if ($event->order->orderable_id) {
            return;
        }
        $this->notifyForEvent(fn ($admin) => new NewOrderNotification($event->order));
    }
}
