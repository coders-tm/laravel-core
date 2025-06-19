<?php

namespace Coderstm\Listeners;

use Illuminate\Support\Arr;
use Kreait\Firebase\Messaging\SendReport;
use Illuminate\Notifications\Events\NotificationFailed;

class DeleteExpiredNotificationTokens
{
    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        $report = Arr::get($event->data, 'report');

        if (!$report instanceof SendReport) {
            return;
        }

        $target = $report->target();

        $event->notifiable->deviceTokens()
            ->where('token', $target->value())
            ->delete();
    }
}
