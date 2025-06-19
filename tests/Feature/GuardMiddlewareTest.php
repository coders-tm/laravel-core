<?php

namespace Coderstm\Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Coderstm\Models\Admin;

class GuardMiddlewareTest extends FeatureTestCase
{
    protected function defineRoutes($router)
    {
        $router->middleware(['auth:sanctum', 'guard:admins'])->get('/foo', function () {
            return guard();
        });
    }

    public function test_handle_with_valid_guard()
    {
        Sanctum::actingAs(Admin::factory()->create());
        $this->get('/foo')->assertStatus(200)->assertSee('admins');
    }

    public function test_handle_with_invalid_guard()
    {
        Sanctum::actingAs(User::factory()->create());
        $this->get('/foo')->assertStatus(401)->assertSee(trans('messages.unauthenticated'));
    }
}
