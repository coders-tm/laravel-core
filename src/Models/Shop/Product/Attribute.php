<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Product\Attribute\Value;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Attribute extends Model
{
    use Core, HasSlug;

    protected $fillable = ['name', 'type', 'slug'];

    protected $with = [];

    public function values()
    {
        return $this->hasMany(Value::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'options');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')->preventOverwrite();
    }

    public function setValues(array $items)
    {
        $items = collect($items);
        $this->values()->whereNotIn('id', $items->pluck('id')->filter())->each(function ($item) {
            $item->delete();
        });
        foreach ($items as $item) {
            $value = $this->values()->updateOrCreate(['id' => isset($item['id']) ? $item['id'] : 'new'], $item);
            if (isset($item['thumbnail']['id'])) {
                $value->media()->sync($item['thumbnail']['id']);
            } else {
                $value->media()->detach();
            }
        }
    }
}
