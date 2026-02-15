<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Database\Factories\Shop\Product\CategoryFactory;
use Coderstm\Models\Shop\Product;
use Coderstm\Traits\Core;
use Coderstm\Traits\Fileable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use Core, Fileable, HasSlug;

    protected $fillable = ['name', 'slug', 'status', 'description', 'parent_id', 'meta_title', 'meta_keywords', 'meta_description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    protected function description(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value ?? '', get: fn ($value) => $value ?? '');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->preventOverwrite();
    }

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }
}
