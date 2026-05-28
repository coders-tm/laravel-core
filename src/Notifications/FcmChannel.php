<?php

namespace Coderstm\Notifications;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;

class FcmChannel
{
    const TOKENS_PER_REQUEST = 500;

    public function __construct(protected Dispatcher $events) {}

    public function send(mixed $notifiable, Notification $notification): ?Collection
    {
        if (! file_exists(base_path(config('firebase.projects.app.credentials')))) {
            return null;
        }
        if (! config('alert.push')) {
            return null;
        }
        $tokens = Arr::wrap($notifiable->routeNotificationFor('fcm', $notification));
        if (empty($tokens)) {
            return null;
        }
        $fcmMessage = $notification->toFcm($notifiable);

        return Collection::make($tokens)->chunk(self::TOKENS_PER_REQUEST)->map(fn ($tokens) => app(Messaging::class)->sendMulticast($fcmMessage, $tokens->all()))->map(fn (MulticastSendReport $report) => $this->checkReportForFailures($notifiable, $notification, $report));
    }

    protected function checkReportForFailures(mixed $notifiable, Notification $notification, MulticastSendReport $report): MulticastSendReport
    {
        Collection::make($report->getItems())->filter(fn (SendReport $report) => $report->isFailure())->each(fn (SendReport $report) => $this->dispatchFailedNotification($notifiable, $notification, $report));

        return $report;
    }

    protected function dispatchFailedNotification(mixed $notifiable, Notification $notification, SendReport $report): void
    {
        $this->events->dispatch(new NotificationFailed($notifiable, $notification, self::class, ['report' => $report]));
    }
}
