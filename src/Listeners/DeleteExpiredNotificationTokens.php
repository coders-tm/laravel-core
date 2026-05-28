<?php

namespace Coderstm\Listeners;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Arr;
use Kreait\Firebase\Messaging\SendReport;

class DeleteExpiredNotificationTokens
{
    public function handle(NotificationFailed $event): void
    {
        $report = Arr::get($event->data, 'report');
        if (! $report instanceof SendReport) {
            return;
        }
        $target = $report->target();
        $event->notifiable->deviceTokens()->where('token', $target->value())->delete();
    }
}
