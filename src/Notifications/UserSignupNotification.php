<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Notification as Template;

class UserSignupNotification extends BaseNotification
{
    public $user;

    public $subject;

    public $message;

    public $subscription;

    public function __construct($user)
    {
        $this->user = $user;
        $this->subscription = $user->subscription();
        $template = Template::default('user:signup');
        $rendered = $template->render(['user' => $user->getShortCodes(), 'subscription' => $this->subscription ? $this->subscription->getShortCodes() : null]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
