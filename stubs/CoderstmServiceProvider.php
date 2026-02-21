<?php

namespace App\Providers;

use Coderstm\Notifications\UserResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

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

            return $baseUrl."?token={$token}&email={$user->email}";
        });

        // Use the core package's UserResetPasswordNotification for custom email templates
        ResetPassword::toMailUsing(function ($user, string $token) {
            $notification = new UserResetPasswordNotification($user, [
                'url' => call_user_func(ResetPassword::$createUrlCallback, $user, $token) ?? null,
                'token' => $token,
                'expires' => now()->addMinutes(config('auth.passwords.'.($user->guard ?? 'users').'.expire', 60))->format('Y-m-d H:i:s'),
            ]);

            return $notification->toMail($user);
        });
    }
}
