<?php

namespace Coderstm\Events;

use Coderstm\Models\Enquiry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnquiryCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $enquiry;

    /**
     * Create a new event instance.
     *
     * @param  Enquiry  $enquiry
     * @return void
     */
    public function __construct($enquiry)
    {
        $this->enquiry = $enquiry;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
