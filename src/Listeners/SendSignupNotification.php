<?php

namespace Coderstm\Listeners;

use Coderstm\Events\UserSubscribed;
use Coderstm\Notifications\UserSignupNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSignupNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(UserSubscribed $event)
    {
        $event->user->notify(new UserSignupNotification($event->user));
    }
}
