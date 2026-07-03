<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryReplyCreated;
use Coderstm\Notifications\EnquiryReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

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
     * @return void
     */
    public function handle(EnquiryReplyCreated $event)
    {
        if ($event->reply->byAdmin()) {
            if ($user = $event->enquiryUser) {
                $user->notify(new EnquiryReplyNotification($event->reply));
            }
        } else {
            admin_notify(new EnquiryReplyNotification($event->reply));
        }
    }
}
