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
    'app_domain' => env('APP_DOMAIN', null),
    'api_prefix' => env('APP_API_PREFIX', 'api'),
    'admin_prefix' => env('APP_ADMIN_PREFIX', 'admin'),
    'user_prefix' => env('APP_USER_PREFIX', 'user'),
    'tunnel_domain' => env('TUNNEL_WEB_DOMAIN', null),
    'reset_password_url' => env('RESET_PASSWORD_PAGE', '/auth/reset-password'),
    'admin_email' => env('APP_ADMIN_EMAIL', null),

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

    'app_url' => env('APP_MEMBER_URL', 'http://localhost/user'),
    'admin_url' => env('APP_ADMIN_URL', 'http://localhost/admins'),

    /*
    |--------------------------------------------------------------------------
    | Theme Asset Path Resolution
    |--------------------------------------------------------------------------
    |
    | This method determines the public path for theme assets during runtime.
    | It first checks for a `.public` file within the theme directory to define
    | a custom path for the assets. If the `.public` file exists and contains
    | a valid path, that path will be used. Otherwise, it defaults to
    | `public/themes/{themeName}`.
    |
    | This approach allows themes to have customizable asset paths, enabling
    | flexible directory structures when managing multiple themes with unique
    | asset requirements.
    |
    */

    'theme_public' => env('MIX_THEME_PUBLIC', false),
];
