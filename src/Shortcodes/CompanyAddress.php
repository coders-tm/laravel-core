<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class CompanyAddress extends Shortcode
{
    public $attributes = [];

    public function render($content)
    {
        $atts = $this->atts();

        return $this->view('shortcodes.company-address', $atts);
    }
}
