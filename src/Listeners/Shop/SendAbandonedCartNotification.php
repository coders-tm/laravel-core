<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Events\Checkout\Abandoned;
use Coderstm\Notifications\Shop\AbandonedCartNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendAbandonedCartNotification implements ShouldQueue
{
    public function handle(Abandoned $event)
    {
        $cart = $event->cart;
        if ($cart->recovery_email_sent_at) {
            return;
        }
        $customer = $cart->customer;
        $email = $cart->email ?: $customer?->email;
        if (! $email) {
            return;
        }
        if ($customer) {
            $customer->notify(new AbandonedCartNotification($cart));
        } else {
            NotificationFacade::route('mail', $email)->notify(new AbandonedCartNotification($cart));
        }
        $cart->update(['recovery_email_sent_at' => now()]);
    }
}
