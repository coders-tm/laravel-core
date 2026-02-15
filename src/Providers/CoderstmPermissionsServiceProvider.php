<?php

namespace Coderstm\Providers;

use Coderstm\Models\Permission;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CoderstmPermissionsServiceProvider extends ServiceProvider
{
    public function register() {}

    public function boot()
    {
        try {
            $permissions = Cache::rememberForever('all_permissions', function () {
                return Permission::get()->pluck('scope');
            });
            $permissions->map(function ($permission) {
                Gate::define($permission, function ($user) use ($permission) {
                    return $user->hasPermission($permission);
                });
            });
            Blade::directive('group', function ($group, $guard = 'users') {
                return "if(guard({$guard}) && user()->hasGroup({$group})) :";
            });
            Blade::directive('endgroup', function ($group) {
                return 'endif;';
            });
        } catch (\Throwable $e) {
            Log::error('Error booting CoderstmPermissionsServiceProvider: '.$e->getMessage());
        }
    }
}
