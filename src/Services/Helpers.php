<?php

namespace Coderstm\Services;

use Browser;
use Stevebauman\Location\Facades\Location;

class Helpers
{
    public static function location()
    {
        try {
            $ip = request()->ip();
            $location = Location::get($ip);
            $device = Browser::browserFamily() . ' on ' . Browser::platformFamily();
            $time = now()->format('M d, Y \a\t g:i a \U\T\C');
            return collect([
                'ip' =>  $ip,
                'device' => $device,
                'location' => $location ? "{$location->regionName}, {$location->countryCode}" : '',
                'time' => $time,
            ]);
        } catch (\Exception $e) {
            return [];
        }
    }
}
