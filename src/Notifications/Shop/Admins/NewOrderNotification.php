<?php

namespace Coderstm\Notifications\Shop\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Notifications\BaseNotification;

class NewOrderNotification extends BaseNotification
{
    public $order;

    public $subject;

    public $message;

    public function __construct($order)
    {
        $this->order = $order;
        $template = Template::default('admin:new-order');
        $rendered = $template->render(['order' => $order->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'customer_name' => $this->order->customer?->name, 'customer_email' => $this->order->customer?->email, 'total' => $this->order->total(), 'status' => $this->order->status];
    }
}
