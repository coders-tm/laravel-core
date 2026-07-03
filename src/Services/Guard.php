<?php

namespace Coderstm\Services;

use Illuminate\Support\Facades\Auth;

class Guard
{
    /**
     * Get the active guard name for the authenticated user.
     */
    public static function detect(): ?string
    {
        $user = Auth::user();

        if ($user) {
            return static::resolveGuardFromModel($user);
        }

        return null;
    }

    /**
     * Resolve the guard name strictly from the user model's class using config/auth.php.
     *
     * @param  mixed  $user
     */
    public static function resolveGuardFromModel($user): ?string
    {
        if (! $user) {
            return null;
        }

        $userClass = get_class($user);
        $providers = config('auth.providers', []);

        foreach ($providers as $providerName => $providerConfig) {
            $modelClass = $providerConfig['model'] ?? null;
            if ($modelClass && $userClass === $modelClass) {
                return static::getGuardForProvider($providerName);
            }
        }

        return null;
    }

    /**
     * Find the guard name that maps to the given provider name.
     */
    protected static function getGuardForProvider(string $providerName): string
    {
        $guards = config('auth.guards', []);

        // Prefer the guard whose name matches the provider name
        if (isset($guards[$providerName]) && ($guards[$providerName]['provider'] ?? null) === $providerName) {
            return $providerName;
        }

        foreach ($guards as $guardName => $guardConfig) {
            if (($guardConfig['provider'] ?? null) === $providerName) {
                return $guardName;
            }
        }

        return $providerName;
    }
}
