<?php

namespace Coderstm\Listeners;

use Coderstm\Events\TaskCreated;
use Coderstm\Notifications\TaskUserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskUsersNotification implements ShouldQueue
{
    public function __construct() {}

    public function handle(TaskCreated $event)
    {
        $users = $event->task->users;
        $users->each(function ($user) use ($event) {
            $user->notify(new TaskUserNotification($event->task, $user));
        });
    }
}
