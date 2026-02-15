<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Shop\LowStockAlert;
use Coderstm\Notifications\Shop\Admins\LowStockNotification;

class SendLowStockNotification extends AdminNotificationListener
{
    public function handle(LowStockAlert $event): void
    {
        $this->notifyForEvent(fn ($admin) => new LowStockNotification($event->variant, $event->inventory, $event->threshold));
    }
}
