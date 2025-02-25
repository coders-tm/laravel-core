<?php

namespace Coderstm\Notifications;

use NotificationChannels\Fcm\FcmChannel as BaseFcmChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class FcmChannel extends BaseFcmChannel
{
    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): ?Collection
    {
        if (!config('alert.push')) {
            return null;
        }

        return parent::send($notifiable, $notification);
    }
}
