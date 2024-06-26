<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Header extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'base-header'],
        'layout' => ['default' => 'classic'],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $layout = $atts['layout'];

        return $this->view("coderstm::shortcodes.headers.$layout", $atts);
    }
}
