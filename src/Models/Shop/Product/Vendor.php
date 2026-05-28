<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Database\Factories\Shop\Product\VendorFactory;
use Coderstm\Models\Shop\Product;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use Core;

    protected $fillable = ['name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    protected static function newFactory()
    {
        return VendorFactory::new();
    }
}
