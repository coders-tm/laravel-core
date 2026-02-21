<?php

namespace Coderstm\Providers;

use Coderstm\Http\Middleware\RequestThemeMiddleware;
use Coderstm\Services\MaskSensitiveConfig;
use Coderstm\Services\Mix;
use Coderstm\Services\Theme;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Mix::class);
        $this->app->singleton('blade.compiler', function ($app) {
            return new MaskSensitiveConfig($app['files'], $app['config']['view.compiled']);
        });
    }

    public function boot(): void
    {
        if ($theme = settings('theme.active')) {
            Theme::set($theme);
        }
        $kernel = $this->app->make('Illuminate\\Contracts\\Http\\Kernel');
        $kernel->pushMiddleware(RequestThemeMiddleware::class);
    }
}
