<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Footer extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'base-header'],
        'layout' => ['default' => 'default'],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $layout = $atts['layout'];

        return $this->view("coderstm::shortcodes.footers.$layout", $atts);
    }
}
