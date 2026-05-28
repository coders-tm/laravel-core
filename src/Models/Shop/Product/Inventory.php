<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Database\Factories\Shop\Product\InventoryFactory;
use Coderstm\Models\Shop\Location;
use Coderstm\Models\Shop\Product;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    use Core;

    protected $fillable = ['available', 'active', 'tracking', 'incoming', 'variant_id', 'location_id'];

    protected $casts = ['active' => 'boolean', 'tracking' => 'boolean'];

    protected $with = [];

    protected $appends = ['trackable'];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function product()
    {
        return $this->hasOneThrough(Product::class, Variant::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function getTrackableAttribute()
    {
        return $this->active && $this->tracking;
    }

    public function scopeSortBy($query, $order_by = 'TITLE_ASC', $order_type = 'asc')
    {
        switch ($order_by) {
            case 'BEST_SELLING':
                break;
            case 'TITLE_DESC':
                $query->select('inventories.*')->leftJoin('variants', 'inventories.variant_id', '=', 'variants.id')->leftJoin('products', 'variants.product_id', '=', 'products.id')->addSelect(DB::raw('products.title AS product_title'))->groupBy('inventories.id')->orderBy('product_title', 'desc');
                break;
            case 'AVAILABLE_ASC':
                $query->orderBy('available', 'asc');
                break;
            case 'AVAILABLE_DESC':
                $query->orderBy('available', 'desc');
                break;
            case 'SKU_ASC':
                $query->select('inventories.*')->leftJoin('variants', 'inventories.variant_id', '=', 'variants.id')->addSelect(DB::raw('variants.sku AS variants_sku'))->groupBy('inventories.id')->orderBy('variants_sku', 'asc');
                break;
            case 'SKU_DESC':
                $query->select('inventories.*')->leftJoin('variants', 'inventories.variant_id', '=', 'variants.id')->addSelect(DB::raw('variants.sku AS variants_sku'))->groupBy('inventories.id')->orderBy('variants_sku', 'desc');
                break;
            case 'TITLE_ASC':
            default:
                $query->select('inventories.*')->leftJoin('variants', 'inventories.variant_id', '=', 'variants.id')->leftJoin('products', 'variants.product_id', '=', 'products.id')->addSelect(DB::raw('products.title AS product_title'))->groupBy('inventories.id')->orderBy('product_title', 'asc');
                break;
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function setAvailable($available = 0, $type = 'adjust')
    {
        $previousAvailable = $this->available;
        $newAvailable = $type == 'adjust' ? $previousAvailable + $available : $available;
        $this->available = $newAvailable;
        $this->save();
    }

    protected static function newFactory()
    {
        return InventoryFactory::new();
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'variant_id' => $this->variant_id, 'location_id' => $this->location_id, 'available' => $this->available ?? 0, 'incoming' => $this->incoming ?? 0, 'location_name' => $this->location?->name, 'location' => $this->location ? ['id' => $this->location->id, 'name' => $this->location->name, 'address' => $this->location->address] : null, 'variant' => $this->variant ? ['id' => $this->variant->id, 'title' => $this->variant->title, 'sku' => $this->variant->sku, 'price' => format_amount($this->variant->price ?? 0)] : null, 'product_name' => $this->variant?->product?->title, 'product_url' => $this->variant ? admin_url("/products/{$this->variant->product_id}") : null, 'is_active' => (bool) $this->active, 'is_tracking' => (bool) $this->tracking, 'is_trackable' => $this->trackable, 'is_low_stock' => $this->available <= 10, 'is_out_of_stock' => $this->available <= 0, 'status' => $this->available > 10 ? 'In Stock' : ($this->available > 0 ? 'Low Stock' : 'Out of Stock')];
    }
}
