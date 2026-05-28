<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Enquiry\Reply;

class EnquiryReplyNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct(Reply $reply)
    {
        $template = $reply->renderNotification();
        $this->subject = $template->subject;
        $this->message = $template->content;
        try {
            $pushTemplate = $reply->renderPushNotification();
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
