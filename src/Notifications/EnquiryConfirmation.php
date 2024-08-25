<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Enquiry;

class EnquiryConfirmation extends BaseNotification
{
    public $subject;
    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Enquiry $enquiry)
    {
        $template = $enquiry->renderNotification();

        $this->subject = $template->subject;
        $this->message = $template->content;

        parent::__construct($this->subject, $this->message);

        if ($this->canSendPush()) {
            $enquiry->sendPushNotify();
        }
    }
}
