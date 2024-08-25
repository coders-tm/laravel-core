<?php

namespace Coderstm\Notifications;

use Coderstm\Models\User;

class SubscriptionExpiredNotification extends BaseNotification
{
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, $subscription)
    {
        $template = $subscription->renderNotification('user:subscription-expired');

        $this->subject = $template->subject;
        $this->message = $template->content;

        parent::__construct($this->subject, $this->message);

        if ($this->canSendPush()) {
            $subscription->sendPushNotify('push:subscription-expired');
        }
    }
}
