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
