<?php

namespace Coderstm\Foundation;

use Coderstm\Foundation\Configuration\ApplicationBuilder;
use Coderstm\Http\Routing\Router;
use Illuminate\Foundation\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * Begin configuring a new Laravel application instance.
     *
     * @return ApplicationBuilder
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
            ->withProviders()
            ->withSingletons([
                'router' => Router::class,
            ]);
    }
}
