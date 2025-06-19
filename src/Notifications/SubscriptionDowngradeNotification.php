<?php

namespace Coderstm\Notifications;

class SubscriptionDowngradeNotification extends BaseNotification
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

        $template = $subscription->renderNotification('user:subscription-downgrade', $shortCodes);

        $this->subject = $template->subject;
        $this->message = $template->content;

        try {
            $pushTemplate = $subscription->renderPushNotification('push:subscription-downgrade', $shortCodes);

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
