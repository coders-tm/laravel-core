<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Calendar extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'calendar'],
        'layout' => ['default' => 'default'],
        'endpoint' => ['default' => null],
    ];

    public function render($content)
    {
        $atts = $this->atts();

        return $this->view('shortcodes.calendar', $atts);
    }
}
