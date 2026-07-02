<?php

namespace Coderstm\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AppTimezoneDate implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Carbon
    {
        if (is_null($value)) {
            return null;
        }

        return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone', 'UTC'));
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        $appTimezone = config('app.timezone', 'UTC');

        return Carbon::parse($value, $appTimezone)->utc()->format('Y-m-d H:i:s');
    }
}
