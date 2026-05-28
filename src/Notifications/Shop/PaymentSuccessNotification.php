<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Notifications\BaseNotification;

class PaymentSuccessNotification extends BaseNotification
{
    public $order;

    public $subject;

    public $message;

    public function __construct($order)
    {
        $this->order = $order;
        $template = Template::default('user:payment-success');
        $rendered = $template->render(['order' => $this->order->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'total' => $this->order->total(), 'payment_status' => $this->order->payment_status];
    }
}
