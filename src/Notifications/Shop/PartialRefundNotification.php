<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Payment;
use Coderstm\Models\Shop\Order;
use Coderstm\Notifications\BaseNotification;

class PartialRefundNotification extends BaseNotification
{
    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

    public $payment;

    public $amount;

    public $reason;

    public $subject;

    public $message;

    /**
     * Create a new notification instance.
     *
     * @param  Order  $order
     * @param  string|null  $reason
     * @return void
     */
    public function __construct($order, Payment $payment, float $amount, $reason = null)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->amount = $amount;
        $this->reason = $reason;

        // Load notification template
        $template = Template::default('user:partial-refund');

        // Render using NotificationTemplateRenderer with order data
        $rendered = $template->render([
            'order' => $this->order->getShortCodes(),
            'refund' => [
                'amount' => format_amount($this->amount),
                'remaining_balance' => format_amount($this->order->refundable_amount),
                'status' => $this->payment->status,
                'payment_method' => $this->payment->gateway_payment_method,
                'reason' => $this->reason,
            ],
        ]);

        parent::__construct($rendered['subject'], $rendered['content']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->formated_id,
            'refund_amount' => format_amount($this->amount),
            'remaining_balance' => format_amount($this->order->refundable_amount),
            'reason' => $this->reason,
        ];
    }
}
