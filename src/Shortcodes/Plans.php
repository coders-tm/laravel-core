<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Subscription\Plan;
use Vedmant\LaravelShortcodes\Shortcode;

class Plans extends Shortcode
{
    public $attributes = ['class' => ['default' => 'plans'], 'layout' => ['default' => 'default']];

    public function render($content)
    {
        $atts = $this->atts();
        $plans = Plan::onlyActive()->get();
        $plans = \Coderstm\Facades\Currency::transform($plans);
        $plans = collect($plans)->map(function ($item) {
            return array_merge($item, ['cur_symbol' => currency_symbol(\Coderstm\Facades\Currency::code())]);
        });

        return $this->view('shortcodes.plans', array_merge($atts, ['content' => $content, 'plans' => $plans]));
    }
}
