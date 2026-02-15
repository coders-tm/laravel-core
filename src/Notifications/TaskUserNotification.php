<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Admin;
use Coderstm\Models\Notification as Template;
use Coderstm\Models\Task;

class TaskUserNotification extends BaseNotification
{
    public $task;

    public $user;

    public $subject;

    public $message;

    public function __construct(Task $task, Admin $user)
    {
        $this->task = $task;
        $this->user = $user;
        $template = Template::default('admin:task-user-notification');
        $rendered = $template->render(['task' => $task->getShortCodes(), 'admin' => $user->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
