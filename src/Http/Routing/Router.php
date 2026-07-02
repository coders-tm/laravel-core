<?php

namespace Coderstm\Http\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    public function __construct(Dispatcher $events, ?Container $container = null)
    {
        parent::__construct($events, $container);
    }

    public function apiResource($name, $controller, array $options = [])
    {
        $only = ['index', 'show', 'store', 'destroySelected', 'restoreSelected', 'forceDestroy', 'forceDestroySelected', 'update', 'destroy', 'restore'];
        if (isset($options['except'])) {
            $only = array_diff($only, (array) $options['except']);
        }

        return $this->resource($name, $controller, array_merge(['only' => $only], $options));
    }

    public function resource($name, $controller, array $options = [])
    {
        if ($this->container && $this->container->bound(ResourceRegistrar::class)) {
            $registrar = $this->container->make(ResourceRegistrar::class);
        } else {
            $registrar = new ResourceRegistrar($this);
        }

        return new PendingResourceRegistration($registrar, $name, $controller, $options);
    }
}
