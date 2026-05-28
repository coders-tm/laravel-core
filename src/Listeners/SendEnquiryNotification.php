<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryCreated;
use Coderstm\Notifications\Admins\EnquiryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnquiryNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(EnquiryCreated $event)
    {
        admin_notify(new EnquiryNotification($event->enquiry));
    }
}
