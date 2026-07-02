<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Coderstm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;

abstract class AdminNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected function adminQuery(?string $permission = 'orders'): Builder
    {
        $model = Coderstm::$adminModel;
        $query = $model::query()->where('is_supper_admin', true);
        if ($permission && method_exists($model, 'permissions')) {
            $query = $query->whereHas('permissions', function ($q) use ($permission) {
                $q->where('scope', 'like', "{$permission}%")->where('permissionables.access', 1);
            });
        }
        if (method_exists($model, 'scopeOnlyActive')) {
            $query = $query->onlyActive();
        }

        return $query;
    }

    protected function notifyForEvent(callable $notification, ?string $permission = 'orders'): void
    {
        foreach ($this->adminQuery($permission)->cursor() as $admin) {
            $admin->notify($notification($admin));
        }
    }
}
