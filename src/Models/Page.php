<?php

namespace Coderstm\Models;

use Coderstm\Contracts\Editorable;
use Coderstm\Traits\Core;
use Coderstm\Traits\HasEditor;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model implements Editorable
{
    use Core, HasEditor, HasSlug;

    protected $dates = ['created_at', 'updated_at'];

    protected $logIgnore = ['data', 'options'];

    protected $fillable = ['parent', 'title', 'slug', 'meta_title', 'meta_keywords', 'meta_description', 'is_active', 'template', 'options'];

    protected $casts = ['is_active' => 'boolean', 'data' => 'json', 'options' => 'json'];

    protected $appends = ['url'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')->preventOverwrite();
    }

    public static function findBySlug(string $slug): static
    {
        return static::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public static function findByTemplate(string $template): static
    {
        return static::where('template', $template)->firstOrFail();
    }

    public function setTemplate($name)
    {
        Page::where('template', $name)->where('id', '<>', $this->id)->update(['template' => null]);
        $this->update(['template' => $name]);

        return $this;
    }

    public function getUrlAttribute()
    {
        $path = $this->slug;
        $parent = $this->parent;
        if ($parent) {
            $path = $parent.'/'.$path;
        }

        return url($path);
    }
}
