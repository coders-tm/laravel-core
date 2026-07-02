<?php

namespace Coderstm\Notifications;

class SubscriptionGraceNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct($subscription)
    {
        $template = $subscription->renderNotification('user:subscription-grace');
        $this->subject = $template->subject;
        $this->message = $template->content;
        $pushTemplate = $subscription->renderPushNotification('user:subscription-grace');
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
