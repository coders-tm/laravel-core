<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\User;

class UserSignupNotification extends BaseNotification
{
    public $user;

    public $subject;

    public $message;

    public $subscription;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->subscription = $user->subscription();
        $template = Template::default('user:signup');
        $rendered = $template->render(['user' => $user->getShortCodes(), 'subscription' => $this->subscription ? $this->subscription->getShortCodes() : null]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
