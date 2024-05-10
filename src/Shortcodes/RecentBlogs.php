<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Blog;
use Vedmant\LaravelShortcodes\Shortcode;

class RecentBlogs extends Shortcode
{
    public $attributes = [
        'count'  => [
            'default'     => 4,
        ],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $blogs = Blog::limit($atts['count'])->get();

        return $this->view('coderstm::shortcodes.recent-blogs', array_merge($atts, [
            'blogs' => $blogs
        ]));
    }
}
