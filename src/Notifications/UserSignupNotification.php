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

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->subscription = $user->subscription();

        $template = Template::default('user:signup');
        $shortCodes = [
            '{{USER_NAME}}' => $this->user->name,
            '{{USER_ID}}' => $this->user->id,
            '{{USER_FIRST_NAME}}' => $this->user->first_name,
            '{{USER_EMAIL}}' => $this->user->email,
            '{{USER_PHONE_NUMBER}}' => $this->user->phone_number,
            '{{PLAN}}' => optional($this->subscription?->plan)->label,
            '{{PLAN_PRICE}}' => optional($this->subscription?->plan)->formatPrice(),
            '{{BILLING_CYCLE}}' => optional($this->subscription?->plan)->interval_label,
        ];

        $subject = replace_short_code($template->subject, $shortCodes);
        $message = replace_short_code($template->content, $shortCodes);

        parent::__construct($subject, $message);
    }
}
