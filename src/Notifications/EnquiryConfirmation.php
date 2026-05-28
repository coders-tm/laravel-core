<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Enquiry;

class EnquiryConfirmation extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct(Enquiry $enquiry)
    {
        $template = $enquiry->renderNotification();
        $this->subject = $template->subject;
        $this->message = $template->content;
        try {
            $pushTemplate = $enquiry->renderPushNotification();
            $this->pushSubject = $pushTemplate->subject;
            $this->pushMessage = $pushTemplate->content;
            $this->pushData = $pushTemplate->data;
            $this->whatsappContent = $pushTemplate->whatsappContent;
        } catch (\Throwable $e) {
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail', FcmChannel::class, TwilioWhatsappChannel::class];
    }
}
