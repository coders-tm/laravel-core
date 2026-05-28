<?php

namespace Coderstm\Policies\Subscription;

use Coderstm\Models\Subscription\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class PlanPolicy
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
        return $admin->can('plans:list');
    }

    public function view(Model $admin, Plan $plan): bool
    {
        return $admin->can('plans:view');
    }

    public function create(Model $admin): bool
    {
        return $admin->can('plans:new');
    }

    public function update(Model $admin, Plan $plan): bool
    {
        return $admin->can('plans:edit') && ($plan->user_id == $admin->id || $plan->hasUser($admin->id));
    }

    public function delete(Model $admin): bool
    {
        return $admin->can('plans:delete');
    }

    public function restore(Model $admin): bool
    {
        return $admin->can('plans:restore');
    }

    public function forceDelete(Model $admin): bool
    {
        return $admin->can('plans:forceDelete');
    }
}
