<?php

namespace Coderstm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BaseNotification extends Notification
{
    use Queueable;

    public $subject;
    public $message;
    public $fromAddress;
    public $fromName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($subject, $message)
    {
        $this->subject = $subject;
        $this->message = $message;
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
        $mailMessage = (new MailMessage)
            ->subject($this->subject);

        if ($this->fromAddress) {
            $mailMessage = $mailMessage->from($this->fromAddress, $this->fromName ?? app('name'));
        }

        return $mailMessage->markdown('emails.notification', [
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

    protected function canSendPush(): bool
    {
        return config('alert.push') || config('alert.whatsapp');
    }
}
