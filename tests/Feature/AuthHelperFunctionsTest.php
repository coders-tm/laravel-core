<?php

namespace Tests\Feature;

use Coderstm\Models\Admin;
use Coderstm\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\BaseTestCase;

class AuthHelperFunctionsTest extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we raise exceptions if something goes wrong in the app
        $this->withoutExceptionHandling();

        // Configure guards for testing
        config([
            'auth.guards.users' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'auth.guards.admins' => [
                'driver' => 'session',
                'provider' => 'admins',
            ],
            'auth.providers.users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ],
            'auth.providers.admins' => [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ],
        ]);
    }

    #[Test]
    public function is_user_returns_false_when_not_authenticated()
    {
        $this->assertFalse(is_user());
    }

    #[Test]
    public function is_admin_returns_false_when_not_authenticated()
    {
        $this->assertFalse(is_admin());
    }

    #[Test]
    public function is_user_returns_true_when_authenticated_as_user_via_session()
    {
        // Assuming User factory exists and 'users' guard is default or configured
        // We might need to adjust based on actual Factory location/name if this fails
        // But typically Coderstm\Models\User should have a factory if it's a standard setup

        // Mocking user instance if factory issues arise, but let's try standard way
        $user = new User;
        $user->id = 1;
        $user->guard = 'users'; // Explicitly set if needed by current logic

        $this->actingAs($user, 'users');
        Auth::shouldUse('users');
        request()->setUserResolver(function () {
            return Auth::user();
        });

        $this->assertTrue(is_user(), 'is_user() should return true for users guard');
        $this->assertFalse(is_admin(), 'is_admin() should return false for users guard');
    }

    #[Test]
    public function is_admin_returns_true_when_authenticated_as_admin_via_session()
    {
        $admin = new Admin;
        $admin->id = 1;
        $admin->guard = 'admins';

        $this->actingAs($admin, 'admins');
        Auth::shouldUse('admins');
        request()->setUserResolver(function () {
            return Auth::user();
        });

        $this->assertTrue(is_admin(), 'is_admin() should return true for admins guard');
        $this->assertFalse(is_user(), 'is_user() should return false for admins guard');
    }
}
