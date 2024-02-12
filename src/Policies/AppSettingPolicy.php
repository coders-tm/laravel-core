<?php

namespace Coderstm\Policies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $admin
     * @param  string  $ability
     * @return void|bool
     */
    public function before(Model $admin, $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $admin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(Model $admin)
    {
        return $admin->can('settings:edit');
    }
}
