<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Coderstm\Http\Middleware\AuthenticateSession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticateSessionMiddlewareTest extends TestCase
{
    public function test_validates_each_guard_independently()
    {
        $admin = Admin::factory()->create(['password' => Hash::make('admin-pass')]);
        $user = User::factory()->create(['password' => Hash::make('user-pass')]);

        Auth::guard('admins')->login($admin);
        Auth::guard('users')->login($user);

        // Simulate prior requests: both password hashes stored in session
        $session = $this->app['session']->driver();
        $session->put('password_hash_admins', $admin->getAuthPassword());
        $session->put('password_hash_users', $user->getAuthPassword());

        // Sanctum Guard finds user first (config('sanctum.guard') = ['users', 'admins'])
        $request = Request::create('/admin/test', 'GET');
        $request->setLaravelSession($session);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $middleware = new AuthenticateSession(app('auth'));

        // Custom middleware validates admin's hash against admin's own password → passes
        $response = $middleware->handle($request, function ($req) {
            return new Response('admin-ok');
        });
        $this->assertEquals('admin-ok', $response->getContent());

        // Vanilla Sanctum would fail here because it validates
        // $request->user()->getAuthPassword() (user-pass) against
        // password_hash_admins (admin-pass) → mismatch
        $this->assertNotEquals(
            $user->getAuthPassword(),
            $admin->getAuthPassword(),
            'Test requires different passwords for admin and user'
        );

        // User route also works (validates user's hash against user's password)
        $request2 = Request::create('/user/test', 'GET');
        $request2->setLaravelSession($session);
        $request2->setUserResolver(function () use ($user) {
            return $user;
        });
        $response2 = $middleware->handle($request2, function ($req) {
            return new Response('user-ok');
        });
        $this->assertEquals('user-ok', $response2->getContent());
    }

    public function test_password_change_still_detected_for_affected_guard()
    {
        $admin = Admin::factory()->create(['password' => Hash::make('old-password')]);
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        Auth::guard('admins')->login($admin);
        Auth::guard('users')->login($user);

        $session = $this->app['session']->driver();
        $session->put('password_hash_admins', $admin->getAuthPassword());
        $session->put('password_hash_users', $user->getAuthPassword());

        $admin->forceFill(['password' => Hash::make('new-password')])->saveQuietly();

        // Admin route: should fail (password changed)
        $request = Request::create('/admin/test', 'GET');
        $request->setLaravelSession($session);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $middleware = new AuthenticateSession(app('auth'));

        try {
            $middleware->handle($request, function ($req) {
                return new Response('ok');
            });
            $this->fail('Expected AuthenticationException for admin route');
        } catch (AuthenticationException $e) {
            $this->assertStringContainsString('admins', implode(', ', $e->guards()));
        }

        // User route: should still pass (own password unchanged)
        $request2 = Request::create('/user/test', 'GET');
        $request2->setLaravelSession($session);
        $request2->setUserResolver(function () use ($user) {
            return $user;
        });

        $response2 = $middleware->handle($request2, function ($req) {
            return new Response('user-ok');
        });
        $this->assertEquals('user-ok', $response2->getContent());
    }
}
