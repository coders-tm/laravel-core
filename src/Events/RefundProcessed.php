<?php

namespace Coderstm\Events;

use Coderstm\Models\Refund;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class RefundProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The refund instance.
     *
     * @var \Coderstm\Models\Refund
     */
    public $refund;

    /**
     * Create a new event instance.
     *
     * @param  \Coderstm\Models\Refund  $refund
     * @return void
     */
    public function __construct(Refund $refund)
    {
        $this->refund = $refund;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
