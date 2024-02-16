<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Admin;
use Coderstm\Models\Notification as Template;
use Coderstm\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TaskUserNotification extends Notification
{
    use Queueable;

    public $task;
    public $user;
    public $subject;
    public $message;
    public $attachments;

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

        if (count($task->media)) {
            $this->attachments = "<p><b><small>Attachments</small></b>:<br>";
            foreach ($task->media as $media) {
                $this->attachments .= "<small><svg style=\"width:10px\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M396.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z\"/></svg><a href=\"{$media->url}\">{$media->name}</a></small><br>";
            }
            $this->attachments .= "</p>";
        }

        $shortCodes = [
            '{{ADMIN_NAME}}' => $this->user->name,
            '{{ADMIN_FIRST_NAME}}' => $this->user->first_name,
            '{{ADMIN_LAST_NAME}}' => $this->user->last_name,
            '{{TASK_ID}}' => $this->task->id,
            '{{TASK_URL}}' => admin_url("tasks/{$this->task->id}?action=edit"),
            '{{TASK_ATTACHMENTS}}' => $this->attachments,
            '{{TASK_SUBJECT}}' => $this->task->subject,
            '{{TASK_MESSAGE}}' => $this->task->message,
            '{{TASK_CREATED_BY}}' => $this->task->user->name,
        ];

        $this->subject = replace_short_code($template->subject, $shortCodes);
        $this->message = replace_short_code($template->content, $shortCodes);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('coderstm::emails.notification', [
                'message' => $this->message
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
