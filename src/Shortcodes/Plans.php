<?php

namespace Coderstm\Shortcodes;

use Coderstm\Models\Subscription\Plan;
use Vedmant\LaravelShortcodes\Shortcode;

class Plans extends Shortcode
{
    public $attributes = [
        'class' => ['default' => 'plans'],
        'layout' => ['default' => 'default'],
    ];

    public function render($content)
    {
        $atts = $this->atts();
        $plans = Plan::onlyActive()->get()->map(function ($item) {
            return array_merge($item->toArray(), [
                'cur_symbol' => currency_symbol()
            ]);
        });

        return $this->view('coderstm::shortcodes.plans', array_merge($atts, [
            'content' => $content,
            'plans' => $plans
        ]));
    }
}
