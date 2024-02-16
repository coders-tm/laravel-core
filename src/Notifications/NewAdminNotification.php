<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Admin;
use Coderstm\Models\Notification as Template;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewAdminNotification extends Notification
{
    use Queueable;

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
            '{{LOGIN_URL}}' => admin_url('auth/login'),
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
