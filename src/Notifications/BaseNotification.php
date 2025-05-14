<?php

namespace Coderstm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Notifications\Messages\MailMessage;

class BaseNotification extends Notification
{
    use Queueable;

    public $subject;
    public $message;
    public $fromAddress;
    public $fromName;

    public $whatsappContent;
    public $whatsappMedia;

    public $pushSubject;
    public $pushMessage;
    public $pushImage;
    public $pushData = [];

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
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable): CloudMessage
    {
        return CloudMessage::fromArray([
            'notification' => array_filter([
                'title' => $this->pushSubject,
                'body' => $this->pushMessage,
                'image' => $this->pushImage,
            ]),
            'topic' => 'global',
            'data' => array_filter($this->pushData)
        ]);
    }

    /**
     * Get the Twilio representation of the notification.
     */
    public function toTwilio($notifiable)
    {
        return html_text($this->whatsappContent);
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
