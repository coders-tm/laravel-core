<?php

use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Foundation\Configuration\ApplicationBuilder as ConfigurationApplicationBuilder;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Coderstm\Http\Routing\Router;

use function Orchestra\Testbench\default_skeleton_path;

if (!class_exists('ApplicationBuilder')) {
    class ApplicationBuilder extends ConfigurationApplicationBuilder
    {
        /**
         * Get the application instance.
         *
         * @return \Illuminate\Foundation\Application
         */
        public function create()
        {
            $this->app->singleton('router', function ($app) {
                return new Router($app['events'], $app);
            });

            return $this->app;
        }
    }
}

if (!class_exists('Application')) {
    class Application extends BaseApplication
    {
        /**
         * Begin configuring a new Laravel application instance.
         *
         * @param  string|null  $basePath
         * @return \Illuminate\Foundation\Configuration\ApplicationBuilder
         */
        public static function configure(?string $basePath = null)
        {
            $basePath = match (true) {
                is_string($basePath) => $basePath,
                default => static::inferBasePath(),
            };

            return (new ApplicationBuilder(new static($basePath)))
                ->withKernels()
                ->withEvents()
                ->withCommands()
                ->withProviders();
        }
    }
}

return Application::configure(default_skeleton_path())
    ->withRouting(
        api: array_filter([
            default_skeleton_path('routes/api.php'),
            __DIR__ . '/../routes/api.php',
        ]),
        web: array_filter([
            default_skeleton_path('routes/web.php'),
            __DIR__ . '/../routes/web.php',
        ]),
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'cart_token',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
