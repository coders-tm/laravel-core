<?php

namespace Coderstm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Coderstm\Models\Shop\Checkout;

class AbandonedCartReminder extends Notification
{
    use Queueable;

    protected $cart;

    public function __construct(Checkout $cart)
    {
        $this->cart = $cart;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url('/shop/cart?session=' . $this->cart->session_id);
        return (new MailMessage)
            ->subject('You left something in your cart!')
            ->greeting('Hi!')
            ->line('It looks like you left some items in your cart.')
            ->action('Return to Cart', $url)
            ->line('Complete your purchase before your items run out!');
    }
}
