<?php

namespace Coderstm\Notifications\Shop\Admins;

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
        $template = Template::default('admin:payment-failed');
        $rendered = $template->render(['order' => $this->order->getShortCodes(), 'reason' => $this->reason]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'customer_name' => optional($this->order->customer)->name, 'customer_email' => optional($this->order->customer)->email, 'total' => $this->order->total(), 'reason' => $this->reason];
    }
}
