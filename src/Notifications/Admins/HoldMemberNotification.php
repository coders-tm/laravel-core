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

    public function __construct(User $user)
    {
        $this->user = $user;
        $template = Template::default('admin:hold-release');
        $data = ['user' => ['name' => $this->user->name, 'id' => $this->user->id, 'first_name' => $this->user->first_name, 'last_name' => $this->user->last_name, 'email' => $this->user->email, 'phone_number' => $this->user->phone_number]];
        $rendered = $template->render($data);
        $this->subject = $rendered['subject'];
        $this->message = $rendered['content'];
        parent::__construct($this->subject, $this->message);
    }
}
