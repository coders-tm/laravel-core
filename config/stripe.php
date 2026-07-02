<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'usd'),

    'currency_locale' => env('STRIPE_CURRENCY_LOCALE', 'en_US'),

];
