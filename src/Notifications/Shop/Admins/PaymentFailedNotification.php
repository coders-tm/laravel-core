<?php

namespace Coderstm\Notifications\Shop\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Shop\Order;
use Coderstm\Notifications\BaseNotification;

class PaymentFailedNotification extends BaseNotification
{
    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

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
    public function __construct($order, $reason = null)
    {
        $this->order = $order;
        $this->reason = $reason;

        // Load notification template
        $template = Template::default('admin:payment-failed');

        // Render using NotificationTemplateRenderer with order data
        $rendered = $template->render([
            'order' => $this->order->getShortCodes(),
            'reason' => $this->reason,
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
            'customer_name' => optional($this->order->customer)->name,
            'customer_email' => optional($this->order->customer)->email,
            'total' => $this->order->total(),
            'reason' => $this->reason,
        ];
    }
}
