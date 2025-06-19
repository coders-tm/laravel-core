<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Blog as Post;
use Vedmant\LaravelShortcodes\Shortcode;

class Blog extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'blog-page'],
        'layout' => ['default' => 'default'],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $blog = request()->input('blog') ?? Post::inRandomOrder()->first();

        if (!$blog) {
            return '';
        }

        return $this->view('shortcodes.blog', array_merge($atts, [
            'blog' => $blog
        ]));
    }
}
