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
        // Define the regex pattern to match all <script> tags within the body
        $pattern = '/<script\b[^>]*>(.*?)<\/script>/is';

        // Step 1: Render the body content and default scripts with Blade
        $this->body = Blade::render($this->body);
        $defaultScripts = view('includes.footer-script')->render();

        // Step 2: Use preg_match_all to extract all <script> tags
        preg_match_all($pattern, $this->body, $matches);

        // Step 3: Store the extracted <script> tags in a separate variable
        // $matches[0] contains the full script tags (opening and closing)
        $this->scripts = implode('', array_merge([$defaultScripts], $matches[0]));

        // Step 4: Remove all <script> tags from the body content
        $this->body = preg_replace($pattern, '', $this->body);

        // Step 5: Optionally extract only the inner content of the <body> tag
        $bodyPattern = '/<body\b[^>]*>(.*?)<\/body>/is';
        if (preg_match($bodyPattern, $this->body, $bodyMatch)) {
            $this->body = $bodyMatch[1]; // This gets the content inside <body> tags
        }

        // Return the extracted scripts and the cleaned body content
        return parent::toArray();
    }


    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('short_desc', function ($query) {
            $url = config('app.url');
            $query->select('*')
                ->addSelect(DB::raw("SUBSTRING_INDEX(REGEXP_REPLACE(REGEXP_REPLACE(body, '<[^>]+>', ' '), '[[:space:]]+', ' '), ' ', 20) AS short_desc"))
                ->addSelect(DB::raw("CONCAT('{$url}/pages/', slug) as url"));
        });
    }
}
