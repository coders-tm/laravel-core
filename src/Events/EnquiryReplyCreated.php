<?php

namespace Coderstm\Events;

use Coderstm\Models\Enquiry\Reply;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EnquiryReplyCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reply;
    public $replyUser;
    public $enquiry;
    public $enquiryUser;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Reply $reply)
    {
        $this->reply = $reply;
        $this->replyUser = $reply->user;
        $this->enquiry = $reply->enquiry;
        $this->enquiryUser = $this->enquiry->user;
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
