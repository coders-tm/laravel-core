<?php

namespace Coderstm\Notifications;

use Coderstm\Events\NotificationDispatched;
use Twilio\Rest\Client;

class TwilioWhatsappChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, BaseNotification $notification): void
    {
        if (! config('alert.whatsapp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('twilio', $notification);
        $from = config('alert.from');

        if (! $to) {
            return;
        }

        $message = $notification->toTwilio($notifiable);

        event(new NotificationDispatched(
            $notifiable, $notification, 'whatsapp', $message, $to
        ));

        $twilio = new Client(config('alert.sid'), config('alert.token'));
        $twilio->messages->create(
            "whatsapp:$to",
            [
                'from' => "whatsapp:$from",
                'body' => html_text($message),
            ]
        );
    }
}
