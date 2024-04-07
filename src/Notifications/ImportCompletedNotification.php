<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Import;
use Coderstm\Models\Notification as Template;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification
{
    use Queueable;

    public Import $import;
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Import $import)
    {
        $this->import = $import;

        $template = Template::default('admin:import-completed');
        $shortCodes = $this->import->getShortCodes();

        $this->subject = replace_short_code($template->subject, $shortCodes);
        $this->message = replace_short_code($template->content, $shortCodes);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
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
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
