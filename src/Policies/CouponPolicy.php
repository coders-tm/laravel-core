<?php

namespace Coderstm\Policies;

use Coderstm\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\HandlesAuthorization;

class CouponPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(Model $admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     */
    public function viewAny(Model $admin): bool
    {
        return $admin->can('coupons:list');
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Model $admin, Coupon $coupon): bool
    {
        return $admin->can('coupons:view');
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Model $admin): bool
    {
        return $admin->can('coupons:new');
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Model $admin, Coupon $coupon): bool
    {
        return $admin->can('coupons:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Model $admin): bool
    {
        return $admin->can('coupons:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(Model $admin): bool
    {
        return $admin->can('coupons:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(Model $admin): bool
    {
        return $admin->can('coupons:forceDelete');
    }
}
