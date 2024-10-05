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
    | Theme Public Path
    |--------------------------------------------------------------------------
    |
    | This value determines the public path used in the Webpack build process
    | for themes. The `MIX_THEME_PUBLIC` environment variable allows the
    | Laravel Mix configuration (found in `webpack.theme.js`) to differentiate
    | between default theme paths and custom theme paths.
    |
    | If the environment variable `MIX_THEME_PUBLIC` is set to "theme", the build
    | will output files to `themes/{themeName}/public`. Otherwise, it defaults to
    | `public/themes/{themeName}`. This setup allows for flexibility in managing
    | theme assets in different directory structures, especially when multiple
    | themes are involved.
    |
    */

    'theme_public' => env('MIX_THEME_PUBLIC', null),
];
