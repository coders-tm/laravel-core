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

    public function __construct($subject, $message)
    {
        $this->subject = $subject;
        $this->message = $message;
    }

    public static function fromTemplate(string $templateType, array $data = []): static
    {
        $template = \Coderstm\Models\Notification::default($templateType);
        $rendered = $template->render($data);

        return new static($rendered['subject'], $rendered['content']);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): NotificationMail
    {
        return new NotificationMail(emailSubject: $this->subject, htmlContent: (string) $this->message, notifiable: $notifiable, fromAddress: $this->fromAddress, fromName: $this->fromName ?? config('app.name'));
    }

    public function toFcm($notifiable): CloudMessage
    {
        return CloudMessage::fromArray(['notification' => array_filter(['title' => $this->pushSubject, 'body' => $this->pushMessage, 'image' => $this->pushImage]), 'topic' => 'global', 'data' => array_filter($this->pushData)]);
    }

    public function toTwilio($notifiable)
    {
        return html_text($this->whatsappContent);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
