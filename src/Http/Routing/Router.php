<?php

namespace Coderstm\Http\Routing;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    /**
     * Route an API resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @return PendingResourceRegistration
     */
    public function apiResource($name, $controller, array $options = [])
    {
        $only = ['index', 'store', 'show', 'update', 'destroy', 'restore', 'bulkRestore', 'bulkDestroy'];

        if (isset($options['except'])) {
            $only = array_diff($only, (array) $options['except']);
        }

        return $this->resource($name, $controller, array_merge([
            'only' => $only,
        ], $options));
    }
}
