<?php

namespace Coderstm\Providers;

use Coderstm\Coderstm;
use Coderstm\Commands;
use Coderstm\Http\Middleware;
use Coderstm\Http\Routing\Router;
use Coderstm\Models\AppSetting;
use Coderstm\Models\PaymentMethod;
use Coderstm\Services\AdminNotification;
use Coderstm\Services\BlogService;
use Coderstm\Services\Currency;
use Coderstm\Services\Guard;
use Coderstm\Services\HookService;
use Coderstm\Services\MaskSensitiveConfig;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoderstmServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();

        // Register Currency as request-scoped service
        $this->app->scoped('currency', function () {
            return new Currency;
        });

        $this->app->bind(ResourceRegistrar::class, \Coderstm\Http\Routing\ResourceRegistrar::class);
        $this->app->singleton('router', function ($app) {
            return new Router($app['events'], $app);
        });

        $this->app->singleton(AdminNotification::class);

        // Register Guard service and facade
        $this->app->singleton('coderstm.guard', function ($app) {
            return new Guard;
        });

        // Register Hook Service
        $this->app->singleton('hooks', function ($app) {
            return new HookService;
        });

        // Register Blog service and facade
        $this->app->singleton('blog', function ($app) {
            return new BlogService;
        });

        // Register MaskSensitiveConfig as a singleton for direct usage (e.g. ThemeController)
        $this->app->singleton(MaskSensitiveConfig::class, function ($app) {
            return new MaskSensitiveConfig(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            );
        });

        // Swap the global Blade compiler to MaskSensitiveConfig
        $this->app->extend('blade.compiler', function ($compiler, $app) {
            return $app->make(MaskSensitiveConfig::class);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRouteMiddleware();
        $this->registerResources();
        $this->registerMigrations();
        $this->registerPublishing();
        $this->registerCommands();

        App::setLocale(app_lang());

        $this->loadConfigFromDatabase();

        Paginator::useBootstrapFive();

        // Register core middleware
        $this->registerCoreMiddleware();

        // Register Request macro for IP Location
        Request::macro('ipLocation', function ($key = null, $default = null) {
            /** @var Request $this */
            $location = $this->attributes->get('ip_location');

            if (! $location) {
                return $default;
            }

            if (is_null($key)) {
                return $location;
            }

            return data_get($location, $key, $default);
        });
    }

    /**
     * Setup the configuration for Coderstm.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(
            $this->packagePath('config/coderstm.php'),
            'coderstm'
        );
    }

    /**
     * Register the package migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (Coderstm::shouldRunMigrations()) {
            $this->loadMigrationsFrom([
                'vendor/laravel/sanctum/database/migrations',
            ]);
            $this->loadMigrationsFrom($this->packagePath('database/migrations'));
        }
    }

    /**
     * Load config from databse.
     *
     * @return void
     */
    protected function loadConfigFromDatabase()
    {
        try {
            // Load app config
            AppSetting::syncConfig();

            // Load payment methods config
            PaymentMethod::syncConfig();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Register the package resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'coderstm');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packagePath('config/coderstm.php') => $this->app->configPath('coderstm.php'),
            ], 'coderstm-config');

            $this->publishes([
                $this->packagePath('database/migrations') => $this->app->databasePath('migrations'),
            ], 'coderstm-migrations');

            $this->publishes([
                $this->packagePath('public') => public_path('statics'),

                $this->packageStubPath('database') => $this->app->databasePath(),
                $this->packageStubPath('routes') => $this->app->basePath('routes'),

                $this->packagePath('resources/views/emails') => resource_path('views/emails'),
                $this->packagePath('resources/views/pdfs') => resource_path('views/pdfs'),
                $this->packagePath('resources/views/shortcodes') => resource_path('views/shortcodes'),
                $this->packagePath('resources/views/includes') => resource_path('views/includes'),
                $this->packagePath('resources/views/layouts') => resource_path('views/layouts'),
                $this->packageStubPath('views/app.blade.php') => resource_path('views/app.blade.php'),

                $this->packageStubPath('theme') => $this->app->basePath('themes/foundation'),
                $this->packageStubPath('webpack.theme.mix.js') => $this->app->basePath('webpack.theme.mix.js'),

                $this->packageStubPath('controllers') => app_path('Http/Controllers'),
                $this->packageStubPath('models') => app_path('Models'),
                $this->packageStubPath('policies') => app_path('Policies'),

                $this->packageStubPath('CoderstmServiceProvider.php') => app_path('Providers/CoderstmServiceProvider.php'),

                $this->packagePath('resources/lang') => resource_path('lang'),
            ], 'coderstm-assets');
        }
    }

    /**
     * Register the package route middlewares.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        Route::aliasMiddleware('guard', Middleware\GuardMiddleware::class);
        Route::aliasMiddleware('subscribed', Middleware\CheckSubscribed::class);
        Route::aliasMiddleware('preserve.json.whitespace', Middleware\PreserveJsonWhitespace::class);
        Route::aliasMiddleware('resolve.currency', Middleware\ResolveCurrency::class);
        Route::aliasMiddleware('resolve.ip', Middleware\ResolveIpAddress::class);
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            Commands\InstallCommand::class,
            Commands\Subscription\Canceled::class,
            Commands\Subscription\GraceCheck::class,
            Commands\Subscription\GraceNotification::class,
            Commands\Subscription\Expired::class,
            Commands\Subscription\ExpiringSoon::class,
            Commands\Subscription\Renew::class,
            Commands\Subscription\ResetUsages::class,
            Commands\Subscription\Resume::class,
            Commands\MigrateSubscriptionFeatures::class,
            Commands\MigrateOrderCommand::class,
            Commands\LangParseCommand::class,
            Commands\UpdateExchangeRates::class,
        ]);
    }

    /**
     * Register core middleware.
     *
     * @return void
     */
    protected function registerCoreMiddleware()
    {
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        // Register resolve ip address middleware
        $kernel->pushMiddleware(Middleware\ResolveIpAddress::class);
    }

    protected function packagePath(string $path)
    {
        return __DIR__.'/../../'.$path;
    }

    protected function packageStubPath(string $path)
    {
        return __DIR__.'/../../stubs/'.$path;
    }
}
