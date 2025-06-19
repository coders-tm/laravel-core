<?php

namespace Coderstm\Listeners;

use Coderstm\Events\TaskCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Coderstm\Notifications\TaskUserNotification;
use Illuminate\Support\Facades\Notification;

class SendTaskUsersNotification implements ShouldQueue
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
     * @param  \Coderstm\Events\TaskCreated  $event
     * @return void
     */
    public function handle(TaskCreated $event)
    {
        $users = $event->task->users;
        $users->each(function ($user) use ($event) {
            $user->notify(new TaskUserNotification($event->task, $user));
        });
    }
}
