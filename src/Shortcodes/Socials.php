<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Socials extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'list-inline'],
        'tooltip' => ['default' => false],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $socials = app_settings('socials');

        return $this->view('coderstm::shortcodes.socials', array_merge($atts, [
            'socials' => $socials,
        ]));
    }
}
