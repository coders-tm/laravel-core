<?php

namespace Coderstm\Services;

use Coderstm\Notifications\BaseNotification;
use Illuminate\Support\Facades\Notification;

class AdminNotification
{
    public function __invoke(BaseNotification $notification)
    {
        return Notification::route('mail', [config('coderstm.admin_email') => config('app.name')])->notify($notification);
    }
}
