<?php

namespace Coderstm\Services;

use Coderstm\Models\Blog as BlogModel;
use Illuminate\Support\Facades\Cache;

class BlogService
{
    public function current()
    {
        return request()->input('blog');
    }

    public function get($key, $default = null)
    {
        $blog = $this->current();

        return $blog ? $blog->{$key} ?? $default : $default;
    }

    public function find($id)
    {
        return BlogModel::find($id);
    }

    public function findBySlug($slug)
    {
        return Cache::rememberForever("blog_{$slug}", function () use ($slug) {
            return BlogModel::findBySlug($slug);
        });
    }

    public function featured()
    {
        return Cache::rememberForever('blog_featured', function () {
            return BlogModel::where('is_active', true)->where('options->featured', true)->latest()->first();
        });
    }

    public function recent($limit = 5)
    {
        $cacheKey = "blog_recent_{$limit}";

        return Cache::rememberForever($cacheKey, function () use ($limit) {
            return BlogModel::where('is_active', true)->latest()->take($limit)->get();
        });
    }

    public function related($blog = null, $limit = 3)
    {
        $blog = $blog ?? $this->current();
        if (! $blog) {
            return collect();
        }
        $cacheKey = "blog_related_{$blog->id}_{$limit}";

        return Cache::rememberForever($cacheKey, function () use ($blog, $limit) {
            $tagIds = $blog->tags->pluck('id')->toArray();
            $query = BlogModel::where('id', '<>', $blog->id)->where('is_active', true)->where(function ($query) use ($blog, $tagIds) {
                if (count($tagIds) > 0) {
                    $query->whereHas('tags', function ($q) use ($tagIds) {
                        $q->whereIn('id', $tagIds);
                    });
                }
                if ($blog->category) {
                    $query->orWhere('category', $blog->category);
                }
            });

            return $query->latest()->take($limit)->get();
        });
    }

    public function byCategory($category, $limit = 5)
    {
        $cacheKey = "blog_category_{$category}_{$limit}";

        return Cache::rememberForever($cacheKey, function () use ($category, $limit) {
            return BlogModel::where('category', $category)->where('is_active', true)->latest()->take($limit)->get();
        });
    }

    public function byTag($tag, $limit = 5)
    {
        $tagType = is_numeric($tag) ? 'id' : 'label';
        $cacheKey = "blog_tag_{$tagType}_{$tag}_{$limit}";

        return Cache::rememberForever($cacheKey, function () use ($tag, $limit) {
            $query = BlogModel::where('is_active', true);
            if (is_numeric($tag)) {
                $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('id', $tag);
                });
            } else {
                $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('label', $tag);
                });
            }

            return $query->latest()->take($limit)->get();
        });
    }

    public function categories($onlyActive = true)
    {
        $cacheKey = 'blog_all_categories'.($onlyActive ? '_active' : '');

        return Cache::rememberForever($cacheKey, function () use ($onlyActive) {
            $query = BlogModel::select('category')->distinct()->whereNotNull('category');
            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('category')->pluck('category');
        });
    }

    public function categoriesWithCount($onlyActive = true)
    {
        $cacheKey = 'blog_categories_with_count'.($onlyActive ? '_active' : '');

        return Cache::rememberForever($cacheKey, function () use ($onlyActive) {
            $query = BlogModel::select('category')->selectRaw('COUNT(*) as count')->whereNotNull('category')->groupBy('category');
            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('category')->get()->map(function ($item) {
                return ['category' => $item->category, 'count' => $item->count];
            });
        });
    }

    public function clearCaches()
    {
        $this->clearRecentBlogCache();
        $this->clearCategoryBlogCaches();
        $this->clearTagBlogCaches();
        if ($blog = $this->current()) {
            $this->clearBlogCache($blog);
        }
    }

    public function clearBlogCache($blog)
    {
        Cache::forget("blog_{$blog->slug}");
        foreach ([3, 5, 10] as $limit) {
            Cache::forget("blog_related_{$blog->id}_{$limit}_{$blog->updated_at->timestamp}");
        }
        if ($blog->category) {
            $this->clearCategoryBlogCache($blog->category);
        }
        foreach ($blog->tags as $tag) {
            $this->clearTagBlogCache($tag->id);
            $this->clearTagBlogCache($tag->label);
        }
    }

    public function clearRecentBlogCache()
    {
        foreach ([3, 5, 10] as $limit) {
            Cache::forget("blog_recent_{$limit}");
        }
    }

    public function clearCategoryBlogCache($category = null)
    {
        if ($category) {
            foreach ([3, 5, 10] as $limit) {
                Cache::forget("blog_category_{$category}_{$limit}");
            }
        }
    }

    public function clearCategoryBlogCaches()
    {
        $categories = BlogModel::select('category')->distinct()->whereNotNull('category')->pluck('category');
        foreach ($categories as $category) {
            $this->clearCategoryBlogCache($category);
        }
        Cache::forget('blog_all_categories_active');
        Cache::forget('blog_all_categories');
        Cache::forget('blog_categories_with_count_active');
        Cache::forget('blog_categories_with_count');
    }

    public function clearTagBlogCache($tag = null)
    {
        if ($tag) {
            $tagType = is_numeric($tag) ? 'id' : 'label';
            foreach ([3, 5, 10] as $limit) {
                Cache::forget("blog_tag_{$tagType}_{$tag}_{$limit}");
            }
        }
    }

    public function clearTagBlogCaches()
    {
        $keys = Cache::get('cache_keys', []);
        foreach ($keys as $key) {
            if (strpos($key, 'blog_tag_') === 0) {
                Cache::forget($key);
            }
        }
    }
}
