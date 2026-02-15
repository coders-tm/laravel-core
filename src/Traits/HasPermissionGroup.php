<?php

namespace Coderstm\Traits;

use Coderstm\Models\Module;
use Coderstm\Models\Permission;
use Illuminate\Support\Collection;

trait HasPermissionGroup
{
    use HasGroup, HasPermission;

    public function getPermissionsViaGroups(): Collection
    {
        return $this->loadMissing('groups', 'groups.permissions')->groups->flatMap(function ($group) {
            return $group->permissions->filter(function ($permission) {
                return ! is_null($permission->pivot->access);
            });
        })->sort()->values();
    }

    public function getAllPermissions(): Collection
    {
        $permissions = $this->permissions;
        if ($this->groups->count()) {
            $permissions = $permissions->merge($this->getPermissionsViaGroups());
        }

        return $permissions->sort()->values();
    }

    public function getModulesAttribute()
    {
        if ($this->is_supper_admin) {
            $modules = Module::all();
        } else {
            $permissions = $this->getAllPermissions()->filter(function ($permission) {
                return $permission->pivot->access == 1;
            });
            $permissionByModule = $permissions->groupBy('module_id');
            $modules = Module::orderBy('sort_order')->find($permissionByModule->keys());
        }

        return $modules->makeHidden(['created_at', 'deleted_at', 'updated_at'])->map(function ($item) {
            return array_merge($item->toArray(), ['label' => __($item['name'])]);
        });
    }

    public function getScopes()
    {
        if ($this->is_supper_admin) {
            return Permission::pluck('scope')->toArray();
        } else {
            $permissions = $this->getAllPermissions()->filter(function ($permission) {
                return $permission->pivot->access == 1;
            });

            return $permissions->pluck('scope')->toArray();
        }
    }
}
