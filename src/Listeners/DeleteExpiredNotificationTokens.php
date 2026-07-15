<?php

namespace Coderstm\Listeners;

use Coderstm\Events\DeviceTokenRemoved;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Arr;
use Kreait\Firebase\Messaging\SendReport;

class DeleteExpiredNotificationTokens
{
    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        $report = Arr::get($event->data, 'report');

        if (! $report instanceof SendReport) {
            return;
        }

        $target = $report->target();

        DeviceTokenRemoved::dispatch($event->notifiable, $target->value());

        $event->notifiable->deviceTokens()
            ->where('token', $target->value())
            ->delete();
    }
}
