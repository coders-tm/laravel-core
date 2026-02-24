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
    'domain' => env('APP_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
    'api_prefix' => env('APP_API_PREFIX', 'api'),
    'admin_prefix' => env('APP_ADMIN_PREFIX', 'admin'),
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

    'app_url' => env('APP_URL', 'http://localhost'),
    'admin_url' => env('APP_ADMIN_URL', 'http://localhost/admin'),

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
            'subscription' => 'coderstm.subscription',
            'checkout' => 'coderstm.shop',
            'email' => [
                'coderstm.admin_email',
                'mail.from.address',
            ],
            'name' => ['mail.from.name'],
            'currency' => 'cashier.currency',
            'timezone' => fn ($value) => date_default_timezone_set($value),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Base currency is the system currency used for storage and calculations.
    | Display currency is determined per-request by middleware.
    |
    */

    'currency' => [
        // Supported currencies list (empty array means allow all)
        'supported' => array_filter(explode(',', env('APP_SUPPORTED_CURRENCIES', ''))),

        // Enable currency auto-detection by user address/IP
        'auto_detect' => (bool) env('CURRENCY_AUTO_DETECT', true),
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
    'product_id' => env('PRODUCT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Subscription
    |--------------------------------------------------------------------------
    |
    | Controls subscription-specific behaviors.
    |
    */

    'subscription' => [
        // When true, activating a late payer anchors from the open invoice's intended
        // start date (last unpaid period start) + plan duration; otherwise, uses today.
        'anchor_from_invoice' => (bool) env('SUBSCRIPTION_ANCHOR_FROM_INVOICE', true),

        // Grace period in days for overdue payments before subscription expires
        'grace_period_days' => (int) env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 0),

        // Freeze configuration
        'freeze_fee' => (float) env('SUBSCRIPTION_FREEZE_FEE', 0.00), // Fee charged per freeze period
        'allow_freeze' => (bool) env('SUBSCRIPTION_ALLOW_FREEZE', true), // Enable/disable freeze functionality

        // Setup fee configuration
        'setup_fee' => (float) env('SUBSCRIPTION_SETUP_FEE', 0.00), // One-time admission fee
    ],

    /*
    |--------------------------------------------------------------------------
    | Shop
    |--------------------------------------------------------------------------
    |
    | This section contains configuration options related to the shop
    | functionality, including cart management, checkout processes,
    | and order handling.
    |
    */

    'shop' => [
        // Number of hours of inactivity before a cart is considered abandoned
        'abandoned_cart_hours' => (int) env('ABANDONED_CART_HOURS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Editor Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration options for the content editor system,
    | including page registry management and file paths.
    |
    */

    'editor' => [
        // Path to the pages directory
        'pages_path' => resource_path('views/pages'),
        // Registry filename for pages/posts (e.g., 'index.json', 'pages.json')
        'registry_path' => resource_path('views/pages/index.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration options for the wallet system,
    | including automatic charging for subscription renewals.
    |
    */

    'wallet' => [
        // Enable wallet functionality
        'enabled' => (bool) env('WALLET_ENABLED', true),

        // Automatically charge from wallet during subscription renewal if balance is available
        'auto_charge_on_renewal' => (bool) env('WALLET_AUTO_CHARGE_ON_RENEWAL', true),
    ],

];
