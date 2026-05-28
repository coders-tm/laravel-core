<?php

namespace Coderstm\Notifications\Shop\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Shop\Product\Variant;
use Coderstm\Notifications\BaseNotification;

class OutOfStockNotification extends BaseNotification
{
    public $variant;

    public $inventory;

    public $subject;

    public $message;

    public function __construct(Variant $variant, $inventory = null)
    {
        $this->variant = $variant;
        $this->inventory = $inventory;
        $template = Template::default('admin:out-of-stock');
        $rendered = $template->render(['variant' => $this->variant->getShortCodes(), 'inventory' => $this->inventory ? $this->inventory->getShortCodes() : null]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toArray($notifiable): array
    {
        return ['variant_id' => $this->variant->id, 'product_name' => $this->variant->product?->title, 'variant_name' => $this->variant->title, 'sku' => $this->variant->sku, 'location' => $this->inventory ? $this->inventory->location?->name : 'All Locations'];
    }
}
