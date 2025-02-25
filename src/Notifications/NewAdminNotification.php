<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Admin;
use Coderstm\Models\Notification as Template;

class NewAdminNotification extends BaseNotification
{
    public $admin;
    public $password;
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Admin $admin, $password = '********')
    {
        $this->admin = $admin;
        $this->password = $password;

        $template = Template::default('admin:new-account');
        $shortCodes = [
            '{{ADMIN_ID}}' => $this->admin->id,
            '{{ADMIN_NAME}}' => $this->admin->name,
            '{{ADMIN_FIRST_NAME}}' => $this->admin->first_name,
            '{{ADMIN_LAST_NAME}}' => $this->admin->last_name,
            '{{ADMIN_EMAIL}}' => $this->admin->email,
            '{{PASSWORD}}' => $this->password,
            '{{LOGIN_URL}}' => admin_url('auth/login', true),
        ];

        $subject = replace_short_code($template->subject, $shortCodes);
        $message = replace_short_code($template->content, $shortCodes);

        parent::__construct($subject, $message);
    }
}
