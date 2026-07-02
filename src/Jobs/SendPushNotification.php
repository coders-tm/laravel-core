<?php

namespace Coderstm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $deviceTokens;

    public function __construct(protected $user, protected array $notification, protected array $data = [])
    {
        $this->deviceTokens = $user->deviceTokens()->pluck('token')->toArray();
    }

    public function handle(): void
    {
        $message = CloudMessage::fromArray(['notification' => $this->notification, 'topic' => 'global', 'data' => $this->data]);
        app(Messaging::class)->sendMulticast($message, $this->deviceTokens);
    }
}
