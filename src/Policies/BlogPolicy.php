<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class BlogPolicy
{
    use HandlesAuthorization;

    public function before(Model $admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    public function viewAny(Model $admin): bool
    {
        return $admin->can('blogs:list');
    }

    public function view(Model $admin): bool
    {
        return $admin->can('blogs:view');
    }

    public function create(Model $admin): bool
    {
        return $admin->can('blogs:new');
    }

    public function update(Model $admin): bool
    {
        return $admin->can('blogs:edit');
    }

    public function delete(Model $admin): bool
    {
        return $admin->can('blogs:delete');
    }

    public function restore(Model $admin): bool
    {
        return $admin->can('blogs:restore');
    }

    public function forceDelete(Model $admin): bool
    {
        return $admin->can('blogs:forceDelete');
    }
}
