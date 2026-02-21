<?php

namespace Coderstm\Notifications;

class SubscriptionDowngradeNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct($subscription)
    {
        $additionalData = ['old_plan' => optional($subscription->oldPlan)->label, 'old_plan_details' => ['label' => optional($subscription->oldPlan)->label]];
        $template = $subscription->renderNotification('user:subscription-downgrade', $additionalData);
        $this->subject = $template->subject;
        $this->message = $template->content;
        try {
            $pushTemplate = $subscription->renderPushNotification('push:subscription-downgrade', $additionalData);
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
