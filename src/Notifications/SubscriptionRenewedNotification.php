<?php

namespace Coderstm\Notifications;

use Coderstm\Models\User;

class SubscriptionRenewedNotification extends BaseNotification
{
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($subscription)
    {
        $template = $subscription->renderNotification('user:subscription-renewed');

        $this->subject = $template->subject;
        $this->message = $template->content;

        try {
            $pushTemplate = $subscription->renderPushNotification('push:subscription-renewed');

            $this->pushSubject = $pushTemplate->subject;
            $this->pushMessage = $pushTemplate->content;
            $this->pushData = $pushTemplate->data;

            $this->whatsappContent = $pushTemplate->whatsappContent;
        } catch (\Exception $e) {
            //throw $e;
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            'mail',
            FcmChannel::class,
            TwilioWhatsappChannel::class,
        ];
    }
}
