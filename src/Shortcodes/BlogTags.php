<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Blog\Tag;
use Vedmant\LaravelShortcodes\Shortcode;

class BlogTags extends Shortcode
{
    public $attributes = [
        'layout'  => [
            'default'     => 'default',
        ],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $tags = Tag::all();

        return $this->view('coderstm::shortcodes.blog-tags', array_merge($atts, [
            'tags' => $tags
        ]));
    }
}
