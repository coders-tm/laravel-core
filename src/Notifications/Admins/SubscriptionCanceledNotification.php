<?php

namespace Coderstm\Notifications\Admins;

use Coderstm\Notifications\BaseNotification;

class SubscriptionCanceledNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct($subscription)
    {
        $template = $subscription->renderNotification('admin:subscription-cancel');
        $this->subject = $template->subject;
        $this->message = $template->content;
        parent::__construct($this->subject, $this->message);
    }
}
