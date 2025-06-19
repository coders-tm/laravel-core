<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Email extends Shortcode
{
    public $attributes = [];

    public function render($content)
    {
        $atts = $this->atts();

        return $this->view('shortcodes.email', $atts);
    }
}
