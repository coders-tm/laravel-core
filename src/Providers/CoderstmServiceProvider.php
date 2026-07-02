<?php

namespace Coderstm\Providers;

use Coderstm\Coderstm;
use Coderstm\Commands;
use Coderstm\Contracts\ConfigurationInterface;
use Coderstm\Http\Middleware;
use Coderstm\Models\AppSetting;
use Coderstm\Models\PaymentMethod;
use Coderstm\Services\AdminNotification;
use Coderstm\Services\ApplicationState;
use Coderstm\Services\BlogService;
use Coderstm\Services\ConfigLoader;
use Coderstm\Services\Currency;
use Coderstm\Services\Guard;
use Coderstm\Services\HookService;
use Coderstm\Services\MaskSensitiveConfig;
use Coderstm\Services\ResponseOptimizer;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoderstmServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->configure();
        $this->app->scoped('currency', function () {
            return new Currency;
        });
        $this->app->bind(ResourceRegistrar::class, \Coderstm\Http\Routing\ResourceRegistrar::class);
        $this->app->singleton(AdminNotification::class);
        $this->app->singleton(ConfigurationInterface::class, ConfigLoader::class);
        $this->app->alias(ConfigurationInterface::class, 'core.config');
        $this->app->singleton('coderstm.guard', function ($app) {
            return new Guard;
        });
        $this->app->singleton('hooks', function ($app) {
            return new HookService;
        });
        $this->app->singleton('blog', function ($app) {
            return new BlogService;
        });
        $this->app->singleton(MaskSensitiveConfig::class, function ($app) {
            return new MaskSensitiveConfig($app['files'], $app['config']['view.compiled'], $app['config']->get('view.relative_hash', false) ? $app->basePath() : '', $app['config']->get('view.cache', true), $app['config']->get('view.compiled_extension', 'php'));
        });
        $this->app->extend('blade.compiler', function ($compiler, $app) {
            return $app->make(MaskSensitiveConfig::class);
        });
    }

    public function boot()
    {
        $this->bootApplicationCore();
        $this->registerRouteMiddleware();
        $this->registerResources();
        $this->registerMigrations();
        $this->registerPublishing();
        $this->registerCommands();
        $this->defineManagementRoutes();
        App::setLocale(app_lang());
        $this->loadConfigFromDatabase();
        Paginator::useBootstrapFive();
        $this->registerCoreMiddleware();
        Request::macro('ipLocation', function ($key = null, $default = null) {
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

    protected function configure()
    {
        $this->mergeConfigFrom($this->packagePath('config/coderstm.php'), 'coderstm');
    }

    protected function registerMigrations()
    {
        if (Coderstm::shouldRunMigrations()) {
            $this->loadMigrationsFrom(['vendor/laravel/sanctum/database/migrations']);
            $this->loadMigrationsFrom($this->packagePath('database/migrations'));
        }
    }

    protected function loadConfigFromDatabase()
    {
        try {
            AppSetting::syncConfig();
            PaymentMethod::syncConfig();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function registerResources()
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'coderstm');
    }

    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->packagePath('config/coderstm.php') => $this->app->configPath('coderstm.php')], 'coderstm-config');
            $this->publishes([$this->packagePath('database/migrations') => $this->app->databasePath('migrations')], 'coderstm-migrations');
            $this->publishes([$this->packagePath('public') => public_path('statics'), $this->packageStubPath('database') => $this->app->databasePath(), $this->packageStubPath('routes') => $this->app->basePath('routes'), $this->packagePath('resources/views/emails') => resource_path('views/emails'), $this->packagePath('resources/views/pdfs') => resource_path('views/pdfs'), $this->packagePath('resources/views/shortcodes') => resource_path('views/shortcodes'), $this->packagePath('resources/views/includes') => resource_path('views/includes'), $this->packagePath('resources/views/layouts') => resource_path('views/layouts'), $this->packageStubPath('views/app.blade.php') => resource_path('views/app.blade.php'), $this->packageStubPath('theme') => $this->app->basePath('themes/foundation'), $this->packageStubPath('webpack.theme.mix.js') => $this->app->basePath('webpack.theme.mix.js'), $this->packageStubPath('controllers') => app_path('Http/Controllers'), $this->packageStubPath('models') => app_path('Models'), $this->packageStubPath('policies') => app_path('Policies'), $this->packageStubPath('CoderstmServiceProvider.php') => app_path('Providers/CoderstmServiceProvider.php'), $this->packagePath('resources/lang') => resource_path('lang')], 'coderstm-assets');
        }
    }

    protected function registerRouteMiddleware()
    {
        Route::aliasMiddleware('guard', Middleware\GuardMiddleware::class);
        Route::aliasMiddleware('subscribed', Middleware\CheckSubscribed::class);
        Route::aliasMiddleware('preserve.json.whitespace', Middleware\PreserveJsonWhitespace::class);
        Route::aliasMiddleware('resolve.currency', Middleware\ResolveCurrency::class);
        Route::aliasMiddleware('resolve.ip', Middleware\ResolveIpAddress::class);
    }

    protected function registerCommands()
    {
        $this->commands([Commands\InstallCommand::class, Commands\Subscription\Canceled::class, Commands\Subscription\GraceCheck::class, Commands\Subscription\GraceNotification::class, Commands\Subscription\Expired::class, Commands\Subscription\ExpiringSoon::class, Commands\Subscription\Renew::class, Commands\Subscription\ResetUsages::class, Commands\Subscription\Resume::class, Commands\MigrateSubscriptionFeatures::class, Commands\MigrateOrderCommand::class, Commands\LangParseCommand::class, Commands\UpdateExchangeRates::class]);
    }

    protected function defineManagementRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }
        if (! $this->app->runningInConsole()) {
            Route::group(['prefix' => 'license'], function () {
                Route::get('/manage', [ApplicationState::class, 'manage'])->middleware('web')->name('license-manage');
                Route::post('/update', [ApplicationState::class, 'update'])->middleware('web')->name('license-update');
            });
        }
    }

    protected function bootApplicationCore()
    {
        $loader = $this->app->make(ConfigurationInterface::class);
        if (! $loader->isValid()) {
            $this->haltApplication();
        }
        $this->app->instance('system.ready', true);
        $this->app->instance('core.loader', $loader);
    }

    protected function registerCoreMiddleware()
    {
        $kernel = $this->app->make('Illuminate\\Contracts\\Http\\Kernel');
        $kernel->pushMiddleware(ApplicationState::class);
        $kernel->pushMiddleware(ResponseOptimizer::class);
        $kernel->pushMiddleware(Middleware\ResolveIpAddress::class);
    }

    protected function isManagementRoute()
    {
        try {
            if (! $this->app->bound('request')) {
                return false;
            }
            $request = $this->app->make('request');
            if (! $request || ! method_exists($request, 'is')) {
                return false;
            }

            return $request->is('*license/manage') || $request->is('*license/update') || $request->is('*install*');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function isInitialized()
    {
        $flag = base_path('storage/.installed');

        return file_exists($flag);
    }

    protected function haltApplication()
    {
        if ($this->isManagementRoute()) {
            return;
        }
        try {
            $htmlPath = $this->packagePath('resources/views/license-required.html');
            if (file_exists($htmlPath)) {
                $html = file_get_contents($htmlPath);
            } else {
                throw new \Exception('Required HTML file not found.');
            }
        } catch (\Throwable $e) {
            $html = '<!DOCTYPE html><html><body><h1>Application Error</h1><p>Initialization failed.</p></body></html>';
        }
        http_response_code(403);
        echo $html;
        exit;
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
