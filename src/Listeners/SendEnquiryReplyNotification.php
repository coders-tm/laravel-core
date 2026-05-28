<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryReplyCreated;
use Coderstm\Notifications\EnquiryReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnquiryReplyNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(EnquiryReplyCreated $event)
    {
        if ($event->reply->byAdmin()) {
            $event->enquiryUser->notify(new EnquiryReplyNotification($event->reply));
        } else {
            admin_notify(new EnquiryReplyNotification($event->reply));
        }
    }
}
