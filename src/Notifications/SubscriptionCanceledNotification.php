<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Subscription;

class SubscriptionCanceledNotification extends BaseNotification
{
    public $subject;

    public $message;

    /**
     * Create a new notification instance.
     *
     * @param  Subscription  $subscription
     * @return void
     */
    public function __construct($subscription)
    {
        $template = $subscription->renderNotification('user:subscription-canceled');

        $this->subject = $template->subject;
        $this->message = $template->content;

        $pushTemplate = $subscription->renderPushNotification('user:subscription-canceled');

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
