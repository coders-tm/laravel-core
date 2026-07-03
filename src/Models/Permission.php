<?php

namespace Coderstm\Models;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use JsonSerializable;

class Permission implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    public $id;

    public $scope;

    public $action;

    public $module_id;

    public function __construct(Module $module, string $action)
    {
        $scope = Str::slug($module->name).':'.strtolower($action);

        $this->id = $scope;
        $this->scope = $scope;
        $this->action = strtolower($action);
        $this->module_id = $module->id;
    }

    /**
     * Generate all permissions for a given module.
     */
    public static function forModule(Module $module)
    {
        return collect(['read', 'write', 'editor'])->map(function ($action) use ($module) {
            return new static($module, $action);
        });
    }

    /**
     * Get all virtual permissions across all modules.
     */
    public static function all($columns = ['*'])
    {
        return Module::all()->flatMap(function ($module) {
            return static::forModule($module);
        });
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }

    // JsonSerializable implementation
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // Arrayable implementation
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope,
            'action' => $this->action,
            'module_id' => $this->module_id,
        ];
    }

    // Jsonable implementation
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
