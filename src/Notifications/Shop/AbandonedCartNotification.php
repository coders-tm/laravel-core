<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Notifications\BaseNotification;

class AbandonedCartNotification extends BaseNotification
{
    public $cart;

    public $subject;

    public $message;

    public function __construct(Checkout $cart)
    {
        $this->cart = $cart;
        $template = Template::default('user:abandoned-cart');
        $rendered = $template->render(['cart' => $this->cart->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toArray($notifiable): array
    {
        return ['cart_id' => $this->cart->id, 'cart_token' => $this->cart->token, 'total' => format_amount($this->cart->grand_total), 'item_count' => $this->cart->line_items->count(), 'abandoned_at' => $this->cart->abandoned_at?->toDateTimeString()];
    }
}
