<?php

namespace Coderstm\Events;

use Coderstm\Models\Enquiry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnquiryCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $enquiry;

    public function __construct(Enquiry $enquiry)
    {
        $this->enquiry = $enquiry;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
