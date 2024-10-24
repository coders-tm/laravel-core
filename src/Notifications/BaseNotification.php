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
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
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
