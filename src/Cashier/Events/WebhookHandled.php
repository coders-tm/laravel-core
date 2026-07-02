<?php

namespace Coderstm\Cashier\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WebhookHandled
{
    use Dispatchable;

    public function __construct(public array $payload) {}
}
