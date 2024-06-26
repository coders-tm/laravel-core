<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class CoderstmServiceProvider extends ServiceProvider
{
    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = null;

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $baseUrl = app_url(config('coderstm.reset_password_url'));

            if ($user->guard === 'admins') {
                $baseUrl = admin_url(config('coderstm.reset_password_url'));
            }

            return $baseUrl . "?token={$token}&email={$user->email}";
        });
    }
}
