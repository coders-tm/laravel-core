<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Services\Theme;
use Coderstm\Tests\TestCase;

class ThemeMiddlewareTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->get('/foo', function () {
            return Theme::active();
        });

        $router->get('/bar', function () {
            return Theme::active();
        })->middleware('theme:foundation');
    }

    public function test_active_theme_is_applied_when_user_requests_with_theme_parameter()
    {
        $this->get('/foo?theme=test')->assertStatus(200)->assertSee('test');
    }

    public function test_active_theme_is_applied_when_middleware_is_used()
    {
        $this->get('/bar')->assertStatus(200)->assertSee('foundation');
    }
}
