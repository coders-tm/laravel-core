<?php

namespace App\Providers;

use Coderstm\Services\Theme;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Config::set("mail.default", 'log');
        Config::set("app.country", 'United States');
        Theme::set('foundation');
    }
}
