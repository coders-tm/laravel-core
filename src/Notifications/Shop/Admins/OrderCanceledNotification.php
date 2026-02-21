<?php

namespace Coderstm\Notifications\Shop\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Notifications\BaseNotification;

class OrderCanceledNotification extends BaseNotification
{
    public $order;

    public $subject;

    public $message;

    public function __construct($order)
    {
        $this->order = $order;
        $template = Template::default('admin:order-canceled');
        $rendered = $template->render(['order' => $order->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'customer_name' => $this->order->customer?->name, 'customer_email' => $this->order->customer?->email, 'total' => $this->order->total(), 'canceled_at' => $this->order->cancelled_at?->toDateTimeString()];
    }
}
