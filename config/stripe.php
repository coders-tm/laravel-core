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

    'locale' => env('STRIPE_CURRENCY_LOCALE', 'en'),

];
