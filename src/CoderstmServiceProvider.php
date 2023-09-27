<?php

namespace Coderstm;

use Coderstm\Commands\CheckExpiredSubscriptions;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\App;
use Coderstm\Commands\CheckHoldUser;
use Illuminate\Pagination\Paginator;
use Coderstm\Commands\InstallCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Coderstm\Commands\SubscriptionsCancel;
use Coderstm\Commands\SubscriptionsInvoice;
use Coderstm\Http\Middleware\CheckSubscribed;
use Coderstm\Http\Middleware\GuardMiddleware;
use Coderstm\Models\Cashier\SubscriptionItem;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        $this->app->bind(\Illuminate\Routing\ResourceRegistrar::class, \Coderstm\Http\Routing\ResourceRegistrar::class);
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

        Relation::morphMap([
            'User' => Coderstm::$userModel,
            'Admin' => Coderstm::$adminModel,
            'Address' => 'Coderstm\Models\Address',
            'Group' => 'Coderstm\Models\Group',
        ]);

        App::setLocale(app_lang());

        Paginator::useBootstrapFive();

        Cashier::ignoreRoutes();
        Cashier::useCustomerModel(Coderstm::$userModel);
        Cashier::useSubscriptionModel(Coderstm::$subscriptionModel);
        Cashier::useSubscriptionItemModel(SubscriptionItem::class);
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
        if (Coderstm::shouldRunMigrations() && $this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->packagePath('database/migrations'));
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
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), 'coderstm');
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
                $this->packageStubPath('routes/web.stub') => $this->app->basePath('routes/coderstm/web.php'),
                $this->packageStubPath('routes/api.stub') => $this->app->basePath('routes/coderstm/api.php'),
            ], 'coderstm-routes');

            $this->publishes([
                $this->packagePath('resources/views') => $this->app->resourcePath('views/vendor/coderstm'),
                $this->packageStubPath('views/app.blade.stub') => $this->app->resourcePath('views/app.blade.php'),
            ], 'coderstm-views');

            $this->publishes([
                $this->packageStubPath('controllers/AdminController.stub') => app_path('Http/Controllers/AdminController.php'),
                $this->packageStubPath('controllers/UserController.stub') => app_path('Http/Controllers/UserController.php'),
                $this->packageStubPath('controllers/EnquiryController.stub') => app_path('Http/Controllers/EnquiryController.php'),
            ], 'coderstm-controllers');

            $this->publishes([
                $this->packageStubPath('models/Admin.stub') => app_path('Models/Admin.php'),
                $this->packageStubPath('models/User.stub') => app_path('Models/User.php'),
                $this->packageStubPath('models/Enquiry.stub') => app_path('Models/Enquiry.php'),
            ], 'coderstm-models');

            $this->publishes([
                $this->packageStubPath('policies/AdminPolicy.stub') => app_path('Policies/AdminPolicy.php'),
                $this->packageStubPath('policies/UserPolicy.stub') => app_path('Policies/UserPolicy.php'),
                $this->packageStubPath('policies/EnquiryPolicy.stub') => app_path('Policies/EnquiryPolicy.php'),
            ], 'coderstm-policies');

            $this->publishes([
                $this->packageStubPath('CoderstmServiceProvider.stub') => app_path('Providers/CoderstmServiceProvider.php'),
                $this->packageStubPath('CoderstmRouteServiceProvider.stub') => app_path('Providers/CoderstmRouteServiceProvider.php'),
            ], 'coderstm-provider');

            $this->publishes([
                $this->packagePath('resources/lang') => base_path('resources/lang/vendor/coderstm'),
            ], 'coderstm-lang');
        }
    }

    /**
     * Register the package route middlewares.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        Route::aliasMiddleware('guard', GuardMiddleware::class);
        Route::aliasMiddleware('subscribed', CheckSubscribed::class);
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SubscriptionsCancel::class,
                SubscriptionsInvoice::class,
                CheckHoldUser::class,
                CheckExpiredSubscriptions::class,
            ]);
        }
    }

    protected function packagePath(string $path)
    {
        return __DIR__ . '/../' . $path;
    }

    protected function packageStubPath(string $path)
    {
        return __DIR__ . '/../stubs/' . $path;
    }
}
