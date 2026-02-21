<?php

namespace Coderstm\Models\Blog;

use Coderstm\Models\Blog;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use HasFactory, HasSlug;

    protected $table = 'blogs_tags';

    protected $fillable = ['label', 'slug'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('label')->saveSlugsTo('slug')->preventOverwrite();
    }

    protected function label(): Attribute
    {
        return Attribute::make(set: fn ($value) => Str::lower($value));
    }

    public function blogs()
    {
        return $this->morphedByMany(Blog::class, 'blogs_taggable');
    }

    public function scopeWithOptions($query)
    {
        return $query->select('id', 'label', 'slug');
    }
}
