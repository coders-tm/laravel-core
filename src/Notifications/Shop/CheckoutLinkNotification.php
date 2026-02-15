<?php

namespace Coderstm\Notifications\Shop;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Notifications\BaseNotification;

class CheckoutLinkNotification extends BaseNotification
{
    public $checkout;

    public $subject;

    public $message;

    public function __construct(Checkout $checkout)
    {
        $this->checkout = $checkout;
        $template = Template::default('user:checkout-link');
        $rendered = $template->render(['checkout' => $checkout->getShortCodes()]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function toArray($notifiable): array
    {
        return ['checkout_id' => $this->checkout->id, 'checkout_token' => $this->checkout->token, 'total' => format_amount($this->checkout->grand_total), 'item_count' => $this->checkout->getLineItemsCount()];
    }
}
