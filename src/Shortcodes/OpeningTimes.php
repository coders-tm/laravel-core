<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class OpeningTimes extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'opening-times'],
        'layout' => ['default' => 'default'],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $opening_times = opening_times();

        return $this->view('shortcodes.opening-times', array_merge($atts, [
            'opening_times' => $opening_times
        ]));
    }
}
