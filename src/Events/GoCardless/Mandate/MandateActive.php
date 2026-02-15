<?php

namespace Coderstm\Events\GoCardless\Mandate;

use Coderstm\Events\GoCardless\GoCardlessEvent;

class MandateActive extends GoCardlessEvent
{
    public $mandateId;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->mandateId = $payload['links']['mandate'] ?? null;
    }
}
