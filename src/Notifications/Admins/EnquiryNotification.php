<?php

namespace Coderstm\Notifications\Admins;

use Coderstm\Models\Enquiry;
use Coderstm\Notifications\BaseNotification;

class EnquiryNotification extends BaseNotification
{
    public $subject;

    public $message;

    public function __construct(Enquiry $enquiry)
    {
        $contactUs = empty($enquiry->subject);
        $type = $contactUs ? 'admin:contact-us-notification' : 'admin:enquiry-notification';
        $template = $enquiry->renderNotification($type);
        $this->subject = $template->subject;
        $this->message = $template->content;
        parent::__construct($this->subject, $this->message);
    }
}
