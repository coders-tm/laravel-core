<?php

namespace Coderstm\Providers;

use Coderstm\Coderstm;
use Coderstm\Commands;
use Coderstm\Http\Middleware;
use Coderstm\Models\AppSetting;
use Coderstm\Models\PaymentMethod;
use Coderstm\Services\ApplicationState;
use Coderstm\Services\Currency;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
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
        $this->app->bind(\Illuminate\Routing\ResourceRegistrar::class, \Coderstm\Http\Routing\ResourceRegistrar::class);
        $this->app->singleton(\Coderstm\Services\AdminNotification::class);
        $this->app->singleton(\Coderstm\Contracts\ConfigurationInterface::class, \Coderstm\Services\ConfigLoader::class);
        $this->app->alias(\Coderstm\Contracts\ConfigurationInterface::class, 'core.config');
        $this->app->singleton('blog', function ($app) {
            return new \Coderstm\Services\BlogService;
        });
        $this->app->singleton('page-service', function ($app) {
            return new \Coderstm\Services\PageService;
        });
        $this->app->singleton(\Coderstm\Services\ShopService::class);
        $this->app->alias(\Coderstm\Services\ShopService::class, 'shop');
        $this->app->register(ViewComposerServiceProvider::class);
    }

    public function boot()
    {
        $this->bootstrapApplicationCore();
        $this->registerRouteMiddleware();
        $this->registerResources();
        $this->registerMigrations();
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerObservers();
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
            $this->loadMigrationsFrom(['vendor/laravel/sanctum/database/migrations', 'vendor/laravel/cashier/database/migrations']);
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
            $this->publishes([$this->packagePath('public') => public_path('statics'), $this->packageStubPath('database') => $this->app->databasePath(), $this->packageStubPath('routes') => $this->app->basePath('routes'), $this->packagePath('resources/views/emails') => resource_path('views/emails'), $this->packagePath('resources/views/pdfs') => resource_path('views/pdfs'), $this->packagePath('resources/views/shortcodes') => resource_path('views/shortcodes'), $this->packagePath('resources/views/includes') => resource_path('views/includes'), $this->packagePath('resources/views/layouts') => resource_path('views/layouts'), $this->packageStubPath('views/app.blade.php') => resource_path('views/app.blade.php'), $this->packageStubPath('theme') => $this->app->basePath('themes/foundation'), $this->packageStubPath('webpack.theme.mix.js') => $this->app->basePath('webpack.theme.mix.js'), $this->packageStubPath('controllers') => app_path('Http/Controllers'), $this->packageStubPath('models') => app_path('Models'), $this->packageStubPath('policies') => app_path('Policies'), $this->packageStubPath('CoderstmServiceProvider.php') => app_path('Providers/CoderstmServiceProvider.php'), $this->packageStubPath('page.stub') => resource_path('page.stub'), $this->packagePath('resources/lang') => resource_path('lang')], 'coderstm-assets');
        }
    }

    protected function registerRouteMiddleware()
    {
        Route::aliasMiddleware('theme', Middleware\ThemeMiddleware::class);
        Route::aliasMiddleware('guard', Middleware\GuardMiddleware::class);
        Route::aliasMiddleware('subscribed', Middleware\CheckSubscribed::class);
        Route::aliasMiddleware('preserve.json.whitespace', Middleware\PreserveJsonWhitespace::class);
        Route::aliasMiddleware('cart.token', Middleware\CartTokenMiddleware::class);
        Route::aliasMiddleware('resolve.currency', Middleware\ResolveCurrency::class);
        Route::aliasMiddleware('resolve.ip', Middleware\ResolveIpAddress::class);
    }

    protected function registerCommands()
    {
        $this->commands([Commands\InstallCommand::class, Commands\CheckCanceledSubscriptions::class, Commands\CheckExpiredSubscriptions::class, Commands\BuildTheme::class, Commands\SubscriptionsRenew::class, Commands\ResetSubscriptionsUsages::class, Commands\ResumeSubscriptions::class, Commands\MigrateSubscriptionFeatures::class, Commands\MigrateOrderCommand::class, Commands\LangParseCommand::class, Commands\ProcessAbandonedCheckouts::class, Commands\RegeneratePages::class, Commands\UpdateExchangeRates::class, Commands\MakePagesJson::class, Commands\UpdateExchangeRates::class]);
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

    protected function bootstrapApplicationCore()
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        if ($this->app->environment('testing') || $this->app->runningUnitTests()) {
            return;
        }
        if (! $this->isInitialized()) {
            return;
        }
        if ($this->isManagementRoute()) {
            return;
        }
        $loader = $this->app->make(\Coderstm\Contracts\ConfigurationInterface::class);
        if (! $loader->isValid()) {
            logger()->error('Core initialization failed.');
            $this->haltApplication();
        }
        $this->app->instance('system.ready', true);
        $this->app->instance('core.loader', $loader);
    }

    protected function registerCoreMiddleware()
    {
        $kernel = $this->app->make('Illuminate\\Contracts\\Http\\Kernel');
        $kernel->pushMiddleware(ApplicationState::class);
        $kernel->pushMiddleware(Middleware\CartTokenMiddleware::class);
        $kernel->pushMiddleware(\Coderstm\Services\ResponseOptimizer::class);
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

    protected function registerObservers()
    {
        \Coderstm\Models\Shop\Product\Inventory::observe(\Coderstm\Observers\InventoryObserver::class);
    }
}
