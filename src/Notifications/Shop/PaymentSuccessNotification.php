<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Shop\Order;
use Coderstm\Notifications\BaseNotification;

class PaymentSuccessNotification extends BaseNotification
{
    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

    public $subject;

    public $message;

    /**
     * Create a new notification instance.
     *
     * @param  Order  $order
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;

        // Load notification template
        $template = Template::default('user:payment-success');

        // Render using NotificationTemplateRenderer with order data
        $rendered = $template->render([
            'order' => $this->order->getShortCodes(),
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
            'total' => $this->order->total(),
            'payment_status' => $this->order->payment_status,
        ];
    }
}
