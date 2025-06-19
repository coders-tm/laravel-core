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
    'domain' => env('CODERSTM_DOMAIN', parse_url(env('APP_URL'), PHP_URL_HOST)),
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
    | Settings to Config Override Mapping
    |--------------------------------------------------------------------------
    |
    | This configuration defines how app settings from the database override
    | Laravel's configuration values. When settings are loaded, the system will
    | automatically update the corresponding config values based on this mapping.
    |
    */

    'settings_override' => [
        'config' => [
            'alias' => 'app',
            'email' => [
                'coderstm.admin_email',
                'mail.from.address',
            ],
            'name' => ['mail.from.name'],
            'currency' => 'cashier.currency',
            'timezone' => fn($value) => date_default_timezone_set($value),
        ],
    ],

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

    /*
    |--------------------------------------------------------------------------
    | NPM Binary Path Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the path to the npm binary used for
    | building themes within the application. The path can be set in
    | the .env file as 'NPM_BIN_PATH'. If it is not defined, it
    | defaults to '/usr/bin'.
    |
    | By allowing the npm binary path to be configured dynamically,
    | this setup enables compatibility with various server environments,
    | ensuring that the application can locate the npm executable
    | regardless of where it is hosted.
    |
    | This flexibility is particularly useful for developers working
    | in different environments or deploying to servers with
    | unique directory structures.
    |
    */

    'npm_bin' => env('NPM_BIN_PATH', '/usr/bin'),

    /*
    |--------------------------------------------------------------------------
    | License Security Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control the license verification and security system.
    | DO NOT modify these unless you understand the security implications.
    |
    */

    'license_key' => env('APP_LICENSE_KEY'),
    'app_id' => env('APP_ID', null),
];
