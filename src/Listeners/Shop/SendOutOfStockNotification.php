<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\OutOfStockAlert;
use Coderstm\Notifications\Shop\Admins\OutOfStockNotification;

class SendOutOfStockNotification extends AdminNotificationListener
{
    public function handle(OutOfStockAlert $event): void
    {
        $this->notifyForEvent(fn ($admin) => new OutOfStockNotification($event->variant, $event->inventory));
    }
}
