<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Spatie\Sluggable\HasSlug;
use Coderstm\Traits\HasEditor;
use Spatie\Sluggable\SlugOptions;
use Coderstm\Interface\Editorable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class Page extends Model implements Editorable
{
    use Core, HasSlug, HasEditor;

    protected $dates = ['created_at', 'updated_at'];

    protected $logIgnore = ['data', 'options'];

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'is_active',
        'template',
        'data',
        'options'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'data' => 'json',
        'options' => 'json',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public static function findBySlug(string $slug): static
    {
        return static::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public static function findByTemplate(string $template): static
    {
        return static::where('template', $template)->firstOrFail();
    }

    public function setTemplate($name)
    {
        Page::where('template', $name)
            ->where('id', '<>', $this->id)
            ->update(['template' => null]);

        $this->update(['template' => $name]);

        return $this;
    }

    protected static function booted()
    {
        parent::booted();

        static::updated(function ($page) {
            if ($slug = $page->getOriginal('slug')) {
                Cache::forget("blog_{$slug}");
            }
            Cache::forget("page_{$page->slug}");
        });

        static::addGlobalScope('url', function ($query) {
            $url = config('app.url');
            $query->select('*')->addSelect(DB::raw("CONCAT('{$url}/pages/', slug) as url"));
        });
    }
}
