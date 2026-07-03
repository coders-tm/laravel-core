<?php

namespace Coderstm\Listeners\Shop;

use Coderstm\Coderstm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Base listener to notify admins for shop-related events.
 * Centralizes querying the configured Admin model with optional scopes
 * and dispatching a notification built from the incoming event.
 */
abstract class AdminNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Build an admin query applying optional scopes when available.
     *
     * @param  string|null  $permission  Optional permission scope to apply (when available on the model)
     */
    protected function adminQuery(?string $permission = 'orders'): Builder
    {
        $model = Coderstm::$adminModel;

        /** @var Builder $query */
        $query = $model::query()->where('is_supper_admin', true);

        // Apply permission scope if present and permission provided
        if ($permission && method_exists($model, 'permissions')) {
            $query = $query->whereHas('permissions', function ($q) use ($permission) {
                $q->where('scope', 'like', "$permission%")->where('permissionables.access', 1);
            });
        }

        // Prefer only active admins if scope exists
        if (method_exists($model, 'scopeOnlyActive')) {
            $query = $query->onlyActive();
        }

        return $query;
    }

    /**
     * Notify all admins for the given event using the provided factory to create the notification.
     *
     * @param  mixed  $event
     * @param  callable  $notification  function ($admin, $event): Notification
     * @param  string|null  $permission  Optional permission to filter admins (defaults to 'orders')
     */
    protected function notifyForEvent(callable $notification, ?string $permission = 'orders'): void
    {
        foreach ($this->adminQuery($permission)->cursor() as $admin) {
            $admin->notify($notification($admin));
        }
    }
}
