<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Models\Shop\Product;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use Core, HasSlug;

    protected $fillable = ['name', 'slug'];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'taggable');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->preventOverwrite();
    }
}
