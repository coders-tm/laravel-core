<?php

namespace Coderstm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceTokenRemoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $tokenable,
        public string $token,
    ) {}
}
