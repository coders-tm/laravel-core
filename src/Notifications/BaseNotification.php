<?php

namespace Coderstm\Notifications;

use Coderstm\Mail\NotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;

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

    public $pushTopic;

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
     * Create notification from database template
     */
    public static function fromTemplate(string $templateType, array $data = []): static
    {
        $template = \Coderstm\Models\Notification::default($templateType);
        $rendered = $template->render($data);

        return new static($rendered['subject'], $rendered['content']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (empty($notifiable->routeNotificationFor('mail'))) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): NotificationMail
    {
        return new NotificationMail(
            emailSubject: $this->subject,
            htmlContent: (string) $this->message,
            notifiable: $notifiable,
            fromAddress: $this->fromAddress,
            fromName: $this->fromName ?? config('app.name')
        );
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
            'topic' => $this->pushTopic,
            'data' => array_map(fn ($v) => (string) $v, array_filter($this->pushData)),
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
