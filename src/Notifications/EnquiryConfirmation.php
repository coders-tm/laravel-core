<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Enquiry;
use Coderstm\Models\Notification as Template;
use Illuminate\Bus\Queueable;
use Illuminate\Support\HtmlString;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EnquiryConfirmation extends Notification
{
    use Queueable;

    public $user;
    public $enquiry;
    public $subject;
    public $message;
    public $attachments;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Enquiry $enquiry)
    {
        $this->enquiry = $enquiry;
        $this->user = $enquiry->user;

        $templateType = $this->enquiry->source ? 'user:enquiry-confirmation' : 'user:enquiry-notification';
        $template = Template::default($templateType);

        if (count($enquiry->media)) {
            $this->attachments = "<p><b><small>Attachments</small></b>:<br>";
            foreach ($enquiry->media as $media) {
                $this->attachments .= "<small><svg style=\"width:10px\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M396.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z\"/></svg><a href=\"{$media->url}\">{$media->name}</a></small><br>";
            }
            $this->attachments .= "</p>";
        }

        $shortCodes = [
            '{{USER_NAME}}' => optional($this->user)->name ?? $enquiry->name,
            '{{USER_ID}}' => optional($this->user)->id,
            '{{USER_FIRST_NAME}}' => optional($this->user)->first_name,
            '{{USER_LAST_NAME}}' => optional($this->user)->last_name,
            '{{USER_EMAIL}}' => optional($this->user)->email ?? $enquiry->email,
            '{{USER_PHONE_NUMBER}}' => optional($this->user)->phone_number ?? $enquiry->phone,
            '{{ENQUIRY_ID}}' => $this->enquiry->id,
            '{{ENQUIRY_URL}}' => member_url("enquiries/{$this->enquiry->id}?action=edit"),
            '{{ENQUIRY_ATTACHMENTS}}' => $this->attachments,
            '{{ENQUIRY_SUBJECT}}' => $this->enquiry->subject,
            '{{ENQUIRY_MESSAGE}}' => $this->enquiry->message,
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
