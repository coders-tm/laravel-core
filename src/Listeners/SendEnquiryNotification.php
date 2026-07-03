<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryCreated;
use Coderstm\Notifications\Admins\EnquiryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnquiryNotification implements ShouldQueue
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
    public function handle(EnquiryCreated $event)
    {
        admin_notify(new EnquiryNotification($event->enquiry));
    }
}
