<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Footer extends Shortcode
{
    public $attributes = ['class' => ['default' => 'base-header'], 'layout' => ['default' => 'default'], 'desc' => ['default' => 'Fitness and Wellbeing is a journey, lifestyle, work life balance or an interest that improves our lives.']];

    public function render($content)
    {
        $atts = $this->atts();
        $layout = $atts['layout'];

        return $this->view("shortcodes.footers.{$layout}", $atts);
    }
}
