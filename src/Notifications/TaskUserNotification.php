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
     * @return void
     */
    public function __construct(Task $task, Admin $user)
    {
        $this->task = $task;
        $this->user = $user;

        $template = Template::default('admin:task-user-notification');
        $shortCodes = array_merge($task->getShortCodes(), $user->getShortCodes());

        $subject = replace_short_code($template->subject, $shortCodes);
        $message = replace_short_code($template->content, $shortCodes);

        parent::__construct($subject, $message);
    }
}
