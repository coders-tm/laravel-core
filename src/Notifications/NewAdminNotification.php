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

    public function __construct(Admin $admin, $password = '********')
    {
        $this->admin = $admin;
        $this->password = $password;
        $template = Template::default('admin:new-account');
        $data = ['admin' => $this->admin->getShortCodes(), 'password' => $this->password, 'login_url' => admin_url('auth/login')];
        $rendered = $template->render($data);
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
