<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Notification as Template;

class TaskUserNotification extends BaseNotification
{
    public $task;

    public $user;

    public $subject;

    public $message;

    public function __construct($task, $user)
    {
        $this->task = $task;
        $this->user = $user;
        $template = Template::default('admin:task-user-notification');
        $rendered = $template->render(['task' => $task->getShortCodes(), 'admin' => $user->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
