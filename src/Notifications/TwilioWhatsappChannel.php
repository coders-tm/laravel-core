<?php

namespace Coderstm\Notifications;

use Twilio\Rest\Client;

class TwilioWhatsappChannel
{

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, BaseNotification $notification): void
    {
        if (!config('alert.whatsapp')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('twilio', $notification);
        $from = config('alert.from');

        if (!$to) {
            return;
        }

        $message = $notification->toTwilio($notifiable);

        $twilio = new Client(config('alert.sid'), config('alert.token'));
        $twilio->messages->create(
            "whatsapp:$to",
            array(
                "from" => "whatsapp:$from",
                "body" => html_text($message),
            )
        );
    }
}
