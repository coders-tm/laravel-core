<?php

namespace Coderstm\Notifications;

class SubscriptionExpiringNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct($subscription, string $type = 'user:subscription-expiring-x-day', int $daysRemaining = 1)
    {
        $template = $subscription->renderNotification($type, ['days_remaining' => $daysRemaining]);
        $this->subject = $template->subject;
        $this->message = $template->content;
        $pushTemplate = $subscription->renderPushNotification($type, ['days_remaining' => $daysRemaining]);
        $this->pushSubject = $pushTemplate->subject;
        $this->pushMessage = $pushTemplate->content;
        $this->pushData = $pushTemplate->data;
        $this->whatsappContent = $pushTemplate->whatsappContent;
    }

    public function via(object $notifiable): array
    {
        return ['mail', FcmChannel::class, TwilioWhatsappChannel::class];
    }
}
