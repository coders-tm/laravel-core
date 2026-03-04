<?php

namespace Coderstm\Services;

use Illuminate\Support\HtmlString;

class Mix
{
    public function __invoke($path, $themeName = null)
    {
        return new HtmlString(Theme::url($path, true, $themeName));
    }
}
