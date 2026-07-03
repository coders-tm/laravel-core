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

    /**
     * Create a new notification instance.
     *
     * @param Task $task
     * @param Admin $user
     * @return void
     */
    public function __construct($task, $user)
    {
        $this->task = $task;
        $this->user = $user;

        $template = Template::default('admin:task-user-notification');

        // Render using NotificationTemplateRenderer with attachments exposed for Blade loops
        $rendered = $template->render([
            'task' => $task->getShortCodes(),
            'admin' => $user->getShortCodes(),
        ]);

        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
