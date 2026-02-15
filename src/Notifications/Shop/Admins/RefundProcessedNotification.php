<?php

namespace Coderstm\Notifications\Shop\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Payment;
use Coderstm\Notifications\BaseNotification;

class RefundProcessedNotification extends BaseNotification
{
    public $order;

    public $payment;

    public $amount;

    public $reason;

    public $subject;

    public $message;

    public function __construct($order, Payment $payment, float $amount, $reason = null)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->amount = $amount;
        $this->reason = $reason;
        $template = Template::default('admin:refund-processed');
        $rendered = $template->render(['order' => $this->order->getShortCodes(), 'refund' => ['amount' => format_amount($this->amount), 'status' => $this->payment->status, 'payment_method' => $this->payment->gateway_payment_method, 'reason' => $this->reason]]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray($notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->formated_id, 'customer_name' => optional($this->order->customer)->name, 'customer_email' => optional($this->order->customer)->email, 'refund_amount' => format_amount($this->amount), 'refund_status' => $this->payment->status, 'reason' => $this->reason];
    }
}
