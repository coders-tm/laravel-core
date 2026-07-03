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
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            $permissions = Cache::rememberForever('all_permissions', function () {
                return Permission::all()->pluck('scope');
            });
            $permissions->map(function ($permission) {
                Gate::define($permission, function ($user) use ($permission) {
                    return $user->hasPermission($permission);
                });
            });

            // Blade directives
            Blade::directive('group', function ($group, $guard = 'users') {
                return "if(guard({$guard}) && user()->hasGroup({$group})) :"; // return this if statement inside php tag
            });

            Blade::directive('endgroup', function ($group) {
                return 'endif;'; // return this endif statement inside php tag
            });
        } catch (\Throwable $e) {
            Log::error('Error booting CoderstmPermissionsServiceProvider: '.$e->getMessage());
        }
    }
}
