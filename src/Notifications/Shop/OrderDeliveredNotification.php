<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Notifications\BaseNotification;

class OrderDeliveredNotification extends BaseNotification
{
    public $order;

    public $subject;

    public $message;

    public function __construct($order)
    {
        $this->order = $order;
        $template = Template::default('user:order-delivered');
        $rendered = $template->render(['order' => $order->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'delivered_at' => $this->order->delivered_at?->toDateTimeString()];
    }
}
