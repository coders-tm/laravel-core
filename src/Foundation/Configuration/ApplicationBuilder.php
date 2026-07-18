<?php

namespace Coderstm\Foundation\Configuration;

use Illuminate\Foundation\Configuration\ApplicationBuilder as BaseApplicationBuilder;

class ApplicationBuilder extends BaseApplicationBuilder
{
    /**
     * Register an array of singleton container bindings to be bound when the application is booting.
     *
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
