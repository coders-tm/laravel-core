<?php

namespace Workbench\App;

use Coderstm\Http\Routing\Router;
use Illuminate\Foundation\Configuration\ApplicationBuilder as ConfigurationApplicationBuilder;

class ApplicationBuilder extends ConfigurationApplicationBuilder
{
    public function create()
    {
        $this->app->singleton('router', fn ($app) => new Router($app['events'], $app));

        return $this->app;
    }
}
