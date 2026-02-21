<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Log;
use Coderstm\Models\Notification as Template;
use Illuminate\Bus\Queueable;

class UserLogin extends BaseNotification
{
    use Queueable;

    public $log;

    public $user;

    public function __construct(Log $log)
    {
        $this->log = $log;
        $this->user = $log->logable;
        $template = Template::default('common:user-login');
        $rendered = $template->render(['user' => $this->user->getShortCodes(), 'log' => ['time' => $this->log->options['time'] ?? null, 'device' => $this->log->options['device'] ?? null, 'location' => $this->log->options['location'] ?? null, 'ip' => $this->log->options['ip'] ?? null]]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
