<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Notifications\BaseNotification;

class PaymentFailedNotification extends BaseNotification
{
    public $order;

    public $reason;

    public $subject;

    public $message;

    public function __construct($order, $reason = null)
    {
        $this->order = $order;
        $this->reason = $reason;
        $template = Template::default('user:payment-failed');
        $rendered = $template->render(['order' => $this->order->getShortCodes(), 'reason' => $this->reason ?? 'Payment processing failed']);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'total' => $this->order->total(), 'reason' => $this->reason];
    }
}
