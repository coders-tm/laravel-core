<?php

namespace Coderstm\Notifications;

class SubscriptionCanceledNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct($subscription)
    {
        $template = $subscription->renderNotification('user:subscription-canceled');
        $this->subject = $template->subject;
        $this->message = $template->content;
        try {
            $pushTemplate = $subscription->renderPushNotification('push:subscription-canceled');
            $this->pushSubject = $pushTemplate->subject;
            $this->pushMessage = $pushTemplate->content;
            $this->pushData = $pushTemplate->data;
            $this->whatsappContent = $pushTemplate->whatsappContent;
        } catch (\Throwable $e) {
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail', FcmChannel::class, TwilioWhatsappChannel::class];
    }
}
