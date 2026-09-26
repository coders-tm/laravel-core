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

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected $user,
        protected array $notification,
        protected array $data = [],
        protected ?string $type = null
    ) {
        $this->deviceTokens = $user->deviceTokens()->pluck('token')->toArray();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = CloudMessage::fromArray([
            'notification' => $this->notification,
            'topic' => 'global',
            'data' => array_filter(array_merge(
                $this->data,
                ['type' => $this->type ?? ($this->data['type'] ?? null)],
            )),
        ]);

        app(Messaging::class)->sendMulticast($message, $this->deviceTokens);
    }
}
