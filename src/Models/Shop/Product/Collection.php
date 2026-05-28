<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Database\Factories\Shop\Product\CollectionFactory;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Product\Collection\Condition;
use Coderstm\Traits\Core;
use Coderstm\Traits\Fileable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Collection extends Model
{
    use Core, Fileable, HasSlug;

    protected $fillable = ['name', 'slug', 'type', 'status', 'conditions_type', 'description', 'meta_title', 'meta_keywords', 'meta_description'];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'collectionable');
    }

    public function conditions()
    {
        return $this->hasMany(Condition::class);
    }

    public function setConditions(array $conditions)
    {
        $conditions = collect($conditions);
        $this->conditions()->whereNotIn('id', $conditions->pluck('id')->filter())->each(function ($item) {
            $item->delete();
        });
        foreach ($conditions as $condition) {
            $this->conditions()->updateOrCreate(['id' => $condition['id']], $condition);
        }
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
        return CollectionFactory::new();
    }
}
