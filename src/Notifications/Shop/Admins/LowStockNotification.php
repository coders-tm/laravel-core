<?php

namespace Coderstm\Notifications\Shop\Admins;

use Coderstm\Models\Notification as Template;
use Coderstm\Models\Shop\Product\Variant;
use Coderstm\Notifications\BaseNotification;

class LowStockNotification extends BaseNotification
{
    public $variant;

    public $inventory;

    public $threshold;

    public $subject;

    public $message;

    public function __construct(Variant $variant, $inventory = null, int $threshold = 10)
    {
        $this->variant = $variant;
        $this->inventory = $inventory;
        $this->threshold = $threshold;
        $template = Template::default('admin:low-stock');
        $rendered = $template->render(['variant' => $this->variant->getShortCodes(), 'inventory' => $this->inventory ? $this->inventory->getShortCodes() : null, 'threshold' => $this->threshold]);
        parent::__construct($rendered['subject'], $rendered['content']);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toArray($notifiable): array
    {
        return ['variant_id' => $this->variant->id, 'product_name' => $this->variant->product?->title, 'variant_name' => $this->variant->title, 'sku' => $this->variant->sku, 'current_stock' => $this->inventory ? $this->inventory->available : $this->variant->inventories->sum('available'), 'threshold' => $this->threshold, 'location' => $this->inventory ? $this->inventory->location?->name : 'All Locations'];
    }
}
