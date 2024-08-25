<?php

namespace Coderstm\Notifications\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\User;
use Coderstm\Notifications\BaseNotification;

class HoldMemberNotification extends BaseNotification
{
    public $user;
    public $status;
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->subject = "[{$user->name}] Hold Release - Member Access Restored";

        $template = Template::default('admin:hold-release');
        $shortCodes = [
            '{{USER_NAME}}' => $this->user->name,
            '{{USER_ID}}' => $this->user->id,
            '{{USER_FIRST_NAME}}' => $this->user->first_name,
            '{{USER_LAST_NAME}}' => $this->user->last_name,
            '{{USER_EMAIL}}' => $this->user->email,
            '{{USER_PHONE_NUMBER}}' => $this->user->phone_number,
        ];

        $this->subject = replace_short_code($template->subject, $shortCodes);
        $this->message = replace_short_code($template->message, $shortCodes);

        parent::__construct($this->subject, $this->message);
    }
}
