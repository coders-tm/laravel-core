<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Blog;
use Vedmant\LaravelShortcodes\Shortcode;

class RecentBlogs extends Shortcode
{
    public $attributes = [
        'limit' => [
            'default' => 4,
        ],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $blogs = app('blog')->recent($atts['limit']);

        return $this->view('shortcodes.recent-blogs', array_merge($atts, [
            'blogs' => $blogs
        ]));
    }
}
