<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Coderstm\Models\Blog\Tag;
use Coderstm\Traits\Fileable;
use Spatie\Sluggable\HasSlug;
use Coderstm\Models\Blog\Comment;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use Core, Fileable, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'is_active',
    ];

    protected $with = [
        'media',
        'thumbnail',
        'tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function createComment(array $attributes = [])
    {
        $comment = new Comment($attributes);
        $comment->user()->associate(user());
        return $this->comments()->save($comment);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable', 'blogs_taggables');
    }


    public function setTags(array $items = [])
    {
        $tags = [];

        foreach ($items as $item) {
            $tag = Tag::firstOrCreate([
                'label' => $item['label'],
            ]);
            $tags[] = $tag->id;
        }

        $this->tags()->sync($tags);

        return $this;
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('short_desc', function ($query) {
            $url = config('app.url');
            $query->select('*')
                ->addSelect(DB::raw("SUBSTRING_INDEX(REGEXP_REPLACE(REGEXP_REPLACE(description, '<[^>]+>', ' '), '[[:space:]]+', ' '), ' ', 20) AS short_desc"))
                ->addSelect(DB::raw("CONCAT('{$url}/blogs/', slug) as url"));
        });
    }
}
