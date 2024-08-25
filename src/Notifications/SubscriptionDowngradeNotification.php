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

        parent::__construct($this->subject, $this->message);

        if ($this->canSendPush()) {
            $subscription->sendPushNotify('push:subscription-downgrade', $shortCodes);
        }
    }
}
