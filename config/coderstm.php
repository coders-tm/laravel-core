<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'domain' => env('APP_DOMAIN', null),
    'tunnel_domain' => env('TUNNEL_WEB_DOMAIN', null),
    'reset_password_url' => env('RESET_PASSWORD_PAGE', '/auth/reset-password'),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'admin_url' => env('APP_ADMIN_URL', 'http://localhost/admin'),
    'app_url' => env('APP_MEMBER_URL', 'http://localhost/user'),
];
