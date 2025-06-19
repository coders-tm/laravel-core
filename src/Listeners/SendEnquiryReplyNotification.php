<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryReplyCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Coderstm\Notifications\EnquiryReplyNotification;
use Coderstm\Notifications\Admins\EnquiryNotification;

class SendEnquiryReplyNotification implements ShouldQueue
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Coderstm\Events\EnquiryReplyCreated  $event
     * @return void
     */
    public function handle(EnquiryReplyCreated $event)
    {
        if ($event->reply->byAdmin()) {
            $event->enquiryUser->notify(new EnquiryReplyNotification($event->reply));
        } else {
            admin_notify(new EnquiryReplyNotification($event->reply));
        }
    }
}
