<?php

namespace Coderstm\Notifications;

class EnquiryConfirmation extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct($enquiry)
    {
        $template = $enquiry->renderNotification();
        $this->subject = $template->subject;
        $this->message = $template->content;
        $pushTemplate = $enquiry->renderPushNotification();
        $this->pushSubject = $pushTemplate->subject;
        $this->pushMessage = $pushTemplate->content;
        $this->pushData = $pushTemplate->data;
        $this->whatsappContent = $pushTemplate->whatsappContent;
    }

    public function via(object $notifiable): array
    {
        return ['mail', FcmChannel::class, TwilioWhatsappChannel::class];
    }
}
