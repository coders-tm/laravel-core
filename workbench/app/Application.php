<?php

namespace Workbench\App;

use Illuminate\Foundation\Application as BaseApplication;

class Application extends BaseApplication
{
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
