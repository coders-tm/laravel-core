<?php

namespace Workbench\App;

use Illuminate\Foundation\Configuration\ApplicationBuilder as ConfigurationApplicationBuilder;

class ApplicationBuilder extends ConfigurationApplicationBuilder
{
    /**
     * Register an array of singleton container bindings to be bound when the application is booting.
     *
     * @param  array  $singletons
     * @return $this
     */
    public function withSingletons(array $singletons)
    {
        foreach ($singletons as $abstract => $concrete) {
            if (is_string($abstract)) {
                $this->app->singleton($abstract, $concrete);
            } else {
                $this->app->singleton($concrete);
            }
        }

        return $this;
    }
}
