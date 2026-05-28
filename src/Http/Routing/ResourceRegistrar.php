<?php

namespace Coderstm\Http\Routing;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Str;

class ResourceRegistrar extends BaseResourceRegistrar
{
    protected static $verbs = ['create' => 'create', 'edit' => 'edit', 'restore' => 'restore', 'destroy' => 'destroy'];

    protected $resourceDefaults = ['index', 'store', 'destroySelected', 'restoreSelected', 'show', 'update', 'destroy', 'restore'];

    public function register($name, $controller, array $options = [])
    {
        if (isset($options['parameters']) && ! isset($this->parameters)) {
            $this->parameters = $options['parameters'];
        }
        if (Str::contains($name, '/')) {
            $this->prefixedResource($name, $controller, $options);

            return;
        }
        $base = $this->getResourceWildcard(last(explode('.', $name)));
        $defaults = $this->resourceDefaults;
        $collection = new RouteCollection;
        foreach ($this->getResourceMethods($defaults, $options) as $m) {
            $route = $this->{'addResource'.Str::studly($m)}($name, $base, $controller, $options);
            if (isset($options['bindingFields'])) {
                $this->setResourceBindingFields($route, $options['bindingFields']);
            }
            $collection->add($route);
        }

        return $collection;
    }

    protected function addResourceIndex($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);
        $action = $this->getResourceAction($name, $controller, 'index', $options);

        return $this->router->get($uri, $action);
    }

    protected function addResourceStore($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);
        $action = $this->getResourceAction($name, $controller, 'store', $options);

        return $this->router->post($uri, $action);
    }

    protected function addResourceUpdate($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);
        $uri = $this->getResourceUri($name).'/{'.$base.'}';
        $action = $this->getResourceAction($name, $controller, 'update', $options);

        return $this->router->match(['POST', 'PUT', 'PATCH'], $uri, $action);
    }

    protected function addResourceDestroy($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);
        $uri = $this->getResourceUri($name).'/{'.$base.'}';
        $action = $this->getResourceAction($name, $controller, 'destroy', $options);

        return $this->router->delete($uri, $action);
    }

    protected function addResourceDestroySelected($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/'.static::$verbs['destroy'];
        $action = $this->getResourceAction($name, $controller, 'destroySelected', $options);

        return $this->router->delete($uri, $action);
    }

    protected function addResourceRestore($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);
        $uri = $this->getResourceUri($name).'/{'.$base.'}/'.static::$verbs['restore'];
        $action = $this->getResourceAction($name, $controller, 'restore', $options);

        return $this->router->post($uri, $action);
    }

    protected function addResourceRestoreSelected($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/'.static::$verbs['restore'];
        $action = $this->getResourceAction($name, $controller, 'restoreSelected', $options);

        return $this->router->post($uri, $action);
    }

    protected function getResourceAction($resource, $controller, $method, $options)
    {
        $name = $this->getResourceRouteName($resource, $method, $options);
        $action = ['as' => $name, 'uses' => $controller.'@'.$method];
        if (isset($options['middleware'])) {
            $action['middleware'] = $options['middleware'];
        }
        if (isset($options['excluded_middleware'])) {
            $action['excluded_middleware'] = $options['excluded_middleware'];
        }
        if (isset($options['wheres'])) {
            $action['where'] = $options['wheres'];
        }
        if (isset($options['missing'])) {
            $action['missing'] = $options['missing'];
        }

        return $action;
    }
}
