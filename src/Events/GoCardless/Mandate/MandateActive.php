<?php

namespace Coderstm\Events\GoCardless\Mandate;

use Coderstm\Events\GoCardless\GoCardlessEvent;

class MandateActive extends GoCardlessEvent
{
    /**
     * The ID of the mandate.
     *
     * @var string
     */
    public $mandateId;

    /**
     * Create a new event instance.
     *
     * @param  array  $payload
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->mandateId = $payload['links']['mandate'] ?? null;
    }
}
