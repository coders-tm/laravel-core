<?php

namespace Coderstm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Twilio\Rest\Client;

class SendWhatsappNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $user, protected string $message)
    {
        $this->phoneNumber = $user->phone_number;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $twilio = new Client(config('alert.sid'), config('alert.token'));
        $twilio->messages->create(
            "whatsapp:{$this->phoneNumber}",
            array(
                "from" => "whatsapp:" . config('alert.from'),
                "body" => html_text($this->message),
            )
        );
    }
}
