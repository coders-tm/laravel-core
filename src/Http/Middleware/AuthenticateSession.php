<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession
{
    public function __construct(protected AuthFactory $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! $request->user()) {
            return $next($request);
        }
        $guards = Collection::make(Arr::wrap(config('sanctum.guard')))->mapWithKeys(fn ($guard) => [$guard => $this->auth->guard($guard)])->filter(fn ($guard) => $guard instanceof SessionGuard);
        $shouldLogout = $guards->filter(fn ($guard, $driver) => $request->session()->has('password_hash_'.$driver))->filter(fn ($guard, $driver) => ! $this->validatePasswordHash($guard, $guard->user()?->getAuthPassword(), $request->session()->get('password_hash_'.$driver)));
        if ($shouldLogout->isNotEmpty()) {
            $shouldLogout->each->logoutCurrentDevice();
            $request->session()->flush();
            throw new AuthenticationException('Unauthenticated.', [...$shouldLogout->keys()->all(), 'sanctum']);
        }

        return tap($next($request), function () use ($request, $guards) {
            foreach ($guards as $driver => $guard) {
                if ($guard->user()) {
                    $this->storePasswordHashInSession($request, $driver);
                }
            }
        });
    }

    protected function storePasswordHashInSession($request, string $guard)
    {
        $guardInstance = $this->auth->guard($guard);
        $request->session()->put(["password_hash_{$guard}" => method_exists($guardInstance, 'hashPasswordForCookie') ? $guardInstance->hashPasswordForCookie($guardInstance->user()->getAuthPassword()) : $guardInstance->user()->getAuthPassword()]);
    }

    protected function validatePasswordHash(SessionGuard $guard, ?string $passwordHash, string $storedValue): bool
    {
        if (method_exists($guard, 'hashPasswordForCookie')) {
            if (hash_equals($guard->hashPasswordForCookie($passwordHash), $storedValue)) {
                return true;
            }
        }

        return hash_equals($passwordHash ?? '', $storedValue);
    }
}
