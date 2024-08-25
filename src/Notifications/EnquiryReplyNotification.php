<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Enquiry\Reply;

class EnquiryReplyNotification extends BaseNotification
{
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Reply $reply)
    {
        $template = $reply->renderNotification();

        $this->subject = $template->subject;
        $this->message = $template->content;

        parent::__construct($this->subject, $this->message);

        if (!$reply->byAdmin() && $this->canSendPush()) {
            $reply->sendPushNotify();
        }
    }
}
