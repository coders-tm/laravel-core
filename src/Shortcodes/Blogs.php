<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Blog;
use Vedmant\LaravelShortcodes\Shortcode;

class Blogs extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'blogs'],
        'paginate' => ['default' => 12],
        'layout' => ['default' => 'default'],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $blogs = Blog::onlyActive()->orderBy('created_at', 'desc')->paginate($atts['paginate']);

        return $this->view('shortcodes.blogs', array_merge($atts, [
            'content' => $content,
            'blogs' => $blogs
        ]));
    }
}
