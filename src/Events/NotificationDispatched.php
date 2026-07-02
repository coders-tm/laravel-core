<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationDispatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notifiable;

    public $notification;

    public $channel;

    public $content;

    public $recipient;

    public function __construct($notifiable, $notification, string $channel, $content, $recipient = null)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
        $this->channel = $channel;
        $this->content = $content;
        $this->recipient = $recipient;
    }
}
