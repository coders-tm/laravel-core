<?php

namespace Coderstm\Policies;

use Coderstm\Models\Coupon;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class CouponPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  mixed  $admin
     * @return mixed
     */
    public function before($admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     *
     * @param  mixed  $admin
     */
    public function viewAny($admin): bool
    {
        return $admin->can('coupons:list');
    }

    /**
     * Determine whether the admin can view the model.
     *
     * @param  mixed  $admin
     * @param  Coupon  $coupon
     */
    public function view($admin, $coupon): bool
    {
        return $admin->can('coupons:view');
    }

    /**
     * Determine whether the admin can create models.
     *
     * @param  mixed  $admin
     */
    public function create($admin): bool
    {
        return $admin->can('coupons:new');
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param  mixed  $admin
     * @param  Coupon  $coupon
     */
    public function update($admin, $coupon): bool
    {
        return $admin->can('coupons:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     *
     * @param  mixed  $admin
     */
    public function delete($admin): bool
    {
        return $admin->can('coupons:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     *
     * @param  mixed  $admin
     */
    public function restore($admin): bool
    {
        return $admin->can('coupons:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     *
     * @param  mixed  $admin
     */
    public function forceDelete($admin): bool
    {
        return $admin->can('coupons:forceDelete');
    }
}
