<?php

namespace Coderstm\Jobs;

use Illuminate\Bus\Queueable;
use Kreait\Firebase\Messaging;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $deviceTokens;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected $user,
        protected array $notification,
        protected array $data = []
    ) {
        $this->deviceTokens = $user->deviceTokens()->pluck('token')->toArray();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if push notification is enabled in the configuration
        if (app_settings('alert')->get('push')) {
            $message = CloudMessage::fromArray([
                'notification' => $this->notification,
                'topic' => 'global',
                'data' => $this->data
            ]);

            app(Messaging::class)->sendMulticast($message, $this->deviceTokens);
        }
    }
}
