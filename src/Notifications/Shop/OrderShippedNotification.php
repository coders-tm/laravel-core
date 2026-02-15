<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Notifications\BaseNotification;

class OrderShippedNotification extends BaseNotification
{
    public $order;

    public $subject;

    public $message;

    public function __construct($order)
    {
        $this->order = $order;
        $template = Template::default('user:order-shipped');
        $rendered = $template->render(['order' => $order->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'tracking_number' => $this->order->tracking_number, 'tracking_company' => $this->order->tracking_company, 'shipped_at' => $this->order->shipped_at?->toDateTimeString()];
    }
}
