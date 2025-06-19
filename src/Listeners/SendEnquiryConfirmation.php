<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryCreated;
use Illuminate\Queue\InteractsWithQueue;
use Coderstm\Notifications\EnquiryConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendEnquiryConfirmation implements ShouldQueue
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
     * @param  \Coderstm\Events\EnquiryCreated  $event
     * @return void
     */
    public function handle(EnquiryCreated $event)
    {
        if ($event->enquiry->user) {
            $event->enquiry->user->notify(new EnquiryConfirmation($event->enquiry));
        } else {
            Notification::route('mail', [
                $event->enquiry->email => $event->enquiry->name
            ])->notify(new EnquiryConfirmation($event->enquiry));
        }
    }
}
