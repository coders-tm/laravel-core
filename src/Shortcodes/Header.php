<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Header extends Shortcode
{
    public $attributes = ['class' => ['default' => 'base-header'], 'layout' => ['default' => 'classic'], 'container' => ['default' => 'container'], 'menu' => ['default' => 'menu-1'], 'ctalabel' => ['default' => 'Get Started'], 'ctalink' => ['default' => '']];

    public function render($content)
    {
        $atts = $this->atts();
        $layout = $atts['layout'];

        return $this->view("shortcodes.headers.{$layout}", $atts);
    }
}
