<?php

namespace Coderstm\Traits;

use Coderstm\Relations\MorphManyPermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasPermission
{
    public function permissions(): MorphManyPermissions
    {
        return new MorphManyPermissions($this);
    }

    public function syncPermissions(Collection $permissions, bool $detach = true)
    {
        $data = $permissions->map(function ($item) {
            $scope = $item['scope'] ?? $item['id'] ?? null;
            $access = isset($item['access']) ? (bool) $item['access'] : null;

            return ['scope' => $scope, 'access' => $access];
        })->filter(function ($item) {
            return ! is_null($item['scope']) && ! is_null($item['access']);
        });
        if ($detach) {
            $this->permissions()->delete();
        }
        foreach ($data as $item) {
            $this->permissions()->updateOrCreate(['scope' => $item['scope']], ['access' => $item['access']]);
        }
        Cache::forget("user_permissions_{$this->id}");

        return $this;
    }

    public function syncPermissionsDetaching(Collection $permissions)
    {
        return $this->syncPermissions($permissions, false);
    }

    public function getAllPermissions(): Collection
    {
        return Cache::remember("user_permissions_{$this->id}", 5, function () {
            return $this->permissions->sort()->values();
        });
    }

    public function hasPermission($permission)
    {
        return (bool) $this->getAllPermissions()->where('pivot.access', 1)->where('scope', $permission)->count();
    }

    public function hasAnyPermission(...$permissions)
    {
        return (bool) $this->getAllPermissions()->where('pivot.access', 1)->whereIn('scope', $permissions)->count();
    }
}
