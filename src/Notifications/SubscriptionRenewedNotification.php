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

        parent::__construct($this->subject, $this->message);

        if ($this->canSendPush()) {
            $subscription->sendPushNotify('push:subscription-renewed');
        }
    }
}
