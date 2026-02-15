<?php

namespace Coderstm\Events\GoCardless;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlowCompleted
{
    use Dispatchable, SerializesModels;

    public $flow;

    public $subscription;

    public $mandateId;

    public $customerId;

    public function __construct($subscription, $flow)
    {
        $this->flow = $flow;
        $this->mandateId = $flow->links->mandate;
        $this->customerId = $flow->links->customer;
        $this->subscription = $subscription;
    }
}
