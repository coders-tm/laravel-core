<?php

namespace Coderstm\Listeners;

use Coderstm\Events\EnquiryCreated;
use Coderstm\Notifications\EnquiryConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendEnquiryConfirmation implements ShouldQueue
{
    public function __construct() {}

    public function handle(EnquiryCreated $event)
    {
        if ($event->enquiry->user) {
            $event->enquiry->user->notify(new EnquiryConfirmation($event->enquiry));
        } else {
            Notification::route('mail', [$event->enquiry->email => $event->enquiry->name])->notify(new EnquiryConfirmation($event->enquiry));
        }
    }
}
