<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\DB;
use Coderstm\Traits\JsonCompressible;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Page extends Model
{
    use Core, HasSlug, JsonCompressible;

    protected $dates = ['created_at', 'updated_at'];

    protected $logIgnore = ['body', 'styles'];

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'is_active',
        'template',
        'data',
        'body',
        'styles',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Interact with the model's JSON data column.
     */
    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => $this->uncompress($value),
            set: fn(array $value) => gzcompress(json_encode($value))
        );
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public static function findBySlug(string $slug)
    {
        return static::where('slug', $slug)->firstOrFail();
    }

    public static function findByTemplate(string $template)
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


    public function render()
    {
        // Define the regex pattern to match script tags within the body
        $pattern = '/<script\b[^>]*>(.*?)<\/script>/is';

        $this->body = Blade::render($this->body);

        // Replace all script tags with an empty string and extract them
        preg_match_all($pattern, $this->body, $matches);

        $defaultScripts = view('coderstm::includes.footer-script')->render();
        $scripts = implode('', array_merge([$defaultScripts], $matches[0]));
        $this->body = preg_replace($pattern, '', $this->body);

        // Add extracted scripts just before the </body> tag
        $this->body = preg_replace('/<\/body>/', "$scripts</body>", $this->body);

        return parent::toArray();
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('short_desc', function ($query) {
            $url = config('app.url');
            $query->select('*')
                ->addSelect(DB::raw("SUBSTRING_INDEX(REGEXP_REPLACE(REGEXP_REPLACE(body, '<[^>]+>', ' '), '[[:space:]]+', ' '), ' ', 20) AS short_desc"))
                ->addSelect(DB::raw("CONCAT('{$url}/', slug) as url"));
        });
    }
}
