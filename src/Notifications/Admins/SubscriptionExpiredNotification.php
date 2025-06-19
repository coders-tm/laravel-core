<?php

namespace Coderstm\Notifications\Admins;

use Coderstm\Notifications\BaseNotification;

class SubscriptionExpiredNotification extends BaseNotification
{
    public $user;
    public $subscription;
    public $status;
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($subscription)
    {
        $template = $subscription->renderNotification('admin:subscription-expired');

        $this->subject = $template->subject;
        $this->message = $template->content;

        parent::__construct($this->subject, $this->message);
    }
}
