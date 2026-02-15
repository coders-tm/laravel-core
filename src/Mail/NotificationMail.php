<?php

namespace Coderstm\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $emailSubject, public string $htmlContent, public object $notifiable, public ?string $fromAddress = null, public ?string $fromName = null)
    {
        $this->setRecipients($notifiable);
    }

    protected function setRecipients(object $notifiable): void
    {
        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            $address = $notifiable->routeNotificationFor('mail', null);
            if (is_array($address)) {
                foreach ($address as $email => $name) {
                    if (! empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->to($email, $name);
                    }
                }
            } elseif ($address) {
                $this->to($address);
            }
        } else {
            $address = $notifiable->email ?? null;
            if ($address) {
                $this->to($address);
            }
        }
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: $this->emailSubject);
        if ($this->fromAddress) {
            $envelope->from($this->fromAddress, $this->fromName ?? config('app.name'));
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->buildHtmlEmail());
    }

    protected function buildHtmlEmail(): string
    {
        return view('emails.notification', ['htmlContent' => $this->htmlContent])->render();
    }

    public function attachments(): array
    {
        return [];
    }
}
