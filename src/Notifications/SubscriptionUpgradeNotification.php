<?php

namespace Coderstm\Notifications;

class SubscriptionUpgradeNotification extends BaseNotification
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
        $shortCodes = [
            '{{OLD_PLAN}}' => optional($subscription->oldPlan)->label,
        ];

        $template = $subscription->renderNotification('user:subscription-upgraded', $shortCodes);

        $this->subject = $template->subject;
        $this->message = $template->content;

        $pushTemplate = $subscription->renderPushNotification('push:subscription-upgraded', $shortCodes);

        $this->pushSubject = $pushTemplate->subject;
        $this->pushMessage = $pushTemplate->content;
        $this->pushData = $pushTemplate->data;

        $this->whatsappContent = $pushTemplate->whatsappContent;
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
