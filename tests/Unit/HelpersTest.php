<?php

namespace Coderstm\Tests\Unit;

use App\Models\User;
use Coderstm\Models\Admin;
use Coderstm\Tests\BaseTestCase;
use Illuminate\Support\Facades\Config;

class HelpersTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->resetConfig();
    }

    protected function resetConfig()
    {
        Config::set('app.url', env('APP_URL'));
        Config::set('coderstm.admin_url', env('CODERSTM_ADMIN_URL'));
        Config::set('coderstm.app_url', env('CODERSTM_APP_URL'));
        Config::set('coderstm.user_prefix', env('CODERSTM_USER_PREFIX'));
        Config::set('coderstm.admin_prefix', env('CODERSTM_ADMIN_PREFIX'));
        Config::set('recaptcha.site_key', env('RECAPTCHA_SITE_KEY'));
    }

    protected function mockRequest($user = null)
    {
        $user = $user ?? User::factory()->make();
        $user->guard = 'users';
        $user->id = 1;
        $user->first_name = 'Test';
        $user->last_name = 'User';
        request()->setUserResolver(fn() => $user);
    }

    public function test_guard_function_returns_user_guard()
    {
        $this->mockRequest();
        $this->assertEquals('users', guard());
    }

    public function test_guard_function_returns_null_if_no_user()
    {
        request()->setUserResolver(fn() => null);
        $this->assertNull(guard());
    }

    public function test_user_function_returns_user_object()
    {
        $user = user();
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Test User', $user->name);
    }

    public function test_user_function_returns_specific_user_property()
    {
        $name = user('name');
        $this->assertEquals('Test User', $name);

        $id = user('id');
        $this->assertEquals(1, $id);
    }

    public function test_user_function_returns_null_if_no_user()
    {
        request()->setUserResolver(fn() => null);

        $user = user();
        $this->assertNull($user);

        $name = user('name');
        $this->assertNull($name);
    }

    public function test_is_user_function_returns_true_for_user_guard()
    {
        $this->mockRequest();
        $this->assertTrue(is_user());
    }

    public function test_is_user_function_returns_false_for_non_user_guard()
    {
        $user = Admin::factory()->make();
        $user->guard = 'admins';
        request()->setUserResolver(fn() => $user);

        $this->assertFalse(is_user());
    }

    public function test_is_user_function_returns_false_if_no_user()
    {
        request()->setUserResolver(fn() => null);
        $this->assertFalse(is_user());
    }

    public function test_is_admin_function_returns_true_for_admin_guard()
    {
        $user = Admin::factory()->make();
        $user->guard = 'admins';
        request()->setUserResolver(fn() => $user);

        $this->assertTrue(is_admin());
    }

    public function test_is_admin_function_returns_false_for_non_admin_guard()
    {
        $this->mockRequest();
        $this->assertFalse(is_admin());
    }

    public function test_is_admin_function_returns_false_if_no_user()
    {
        request()->setUserResolver(fn() => null);
        $this->assertFalse(is_admin());
    }

    public function test_base_url_with_default_path()
    {
        $this->assertEquals('http://localhost', base_url());
    }

    public function test_base_url_with_relative_path()
    {
        $this->assertEquals('http://localhost/about', base_url('about'));
    }

    public function test_base_url_with_absolute_path()
    {
        $this->assertEquals('http://localhost/about', base_url('/about'));
    }

    // public function test_base_url_with_trailing_slash_in_config()
    // {
    //     // Mock the config value for the app URL with a trailing slash
    //     config(['app.url' => 'https://example.com/']);

    //     // Test with no path
    //     $result = base_url();
    //     $this->assertEquals('https://example.com', $result);

    //     // Test with a relative path
    //     $result = base_url('services');
    //     $this->assertEquals('https://example.com/services', $result);

    //     // Test with an absolute path
    //     $result = base_url('/blog');
    //     $this->assertEquals('https://example.com/blog', $result);
    // }

    // public function test_base_url_with_empty_path()
    // {
    //     // Mock the config value for the app URL
    //     config(['app.url' => 'https://example.com']);

    //     // Test with an empty string as the path
    //     $result = base_url('');
    //     $this->assertEquals('https://example.com', $result);
    // }

    // public function test_base_url_with_complex_app_url()
    // {
    //     // Mock the config value for the app URL with a complex structure
    //     config(['app.url' => 'https://example.com:8080/subpath']);

    //     // Test with no path
    //     $result = base_url();
    //     $this->assertEquals('https://example.com:8080/subpath', $result);

    //     // Test with a relative path
    //     $result = base_url('dashboard');
    //     $this->assertEquals('https://example.com:8080/subpath/dashboard', $result);

    //     // Test with an absolute path
    //     $result = base_url('/settings');
    //     $this->assertEquals('https://example.com:8080/subpath/settings', $result);
    // }

    public function test_admin_url_with_default_path()
    {
        $this->assertEquals('http://localhost/admin', admin_url());
        $this->assertEquals('http://localhost/admin/dashboard', admin_url('dashboard'));
        $this->assertEquals('http://localhost/admin/dashboard', admin_url('/dashboard'));
    }

    // public function test_admin_url_with_trailing_slash_in_config()
    // {
    //     // Mock the config value for the admin URL with a trailing slash
    //     config(['coderstm.admin_url' => 'https://admin.example.com/']);

    //     // Test with no additional path
    //     $result = admin_url();
    //     $this->assertEquals('https://admin.example.com', $result);

    //     // Test with a relative path
    //     $result = admin_url('settings');
    //     $this->assertEquals('https://admin.example.com/settings', $result);

    //     // Test with an absolute path
    //     $result = admin_url('/settings');
    //     $this->assertEquals('https://admin.example.com/settings', $result);
    // }

    // public function test_admin_url_with_absolute_flag()
    // {
    //     // Mock the config value for the admin URL with a complex path
    //     config(['coderstm.admin_url' => 'https://admin.example.com:8080/subpath']);

    //     // Test with the absolute flag set to true
    //     $result = admin_url('users', true);
    //     $this->assertEquals('https://admin.example.com:8080/users', $result);

    //     // Test with no additional path and absolute flag
    //     $result = admin_url('', true);
    //     $this->assertEquals('https://admin.example.com:8080', $result);

    //     // Test with an absolute path and absolute flag
    //     $result = admin_url('/users', true);
    //     $this->assertEquals('https://admin.example.com:8080/users', $result);
    // }

    // public function test_admin_url_absolute_without_subpath()
    // {
    //     // Mock the config value for the admin URL
    //     config(['coderstm.admin_url' => 'https://admin.example.com:8080/subpath']);

    //     // Test absolute flag removing subpath
    //     $result = admin_url('profile', true);
    //     $this->assertEquals('https://admin.example.com:8080/profile', $result);
    // }

    // public function test_admin_url_with_empty_path()
    // {
    //     // Mock the config value for the admin URL
    //     config(['coderstm.admin_url' => 'https://admin.example.com']);

    //     // Test with an empty path
    //     $result = admin_url('', false);
    //     $this->assertEquals('https://admin.example.com', $result);
    // }

    public function test_app_url_with_default_path()
    {
        $this->assertEquals('http://localhost/user', app_url());
        $this->assertEquals('http://localhost/user/dashboard', app_url('dashboard'));
        $this->assertEquals('http://localhost/user/dashboard', app_url('/dashboard'));
    }

    // public function test_app_url_with_trailing_slash_in_config()
    // {
    //     // Mock the config value for the app URL with a trailing slash
    //     config(['coderstm.app_url' => 'https://example.com/']);

    //     // Test with no additional path
    //     $result = app_url();
    //     $this->assertEquals('https://example.com/', $result);

    //     // Test with a relative path
    //     $result = app_url('profile');
    //     $this->assertEquals('https://example.com/profile', $result);

    //     // Test with an absolute path
    //     $result = app_url('/profile');
    //     $this->assertEquals('https://example.com/profile', $result);
    // }

    // public function test_app_url_with_empty_path()
    // {
    //     // Mock the config value for the app URL
    //     config(['coderstm.app_url' => 'https://example.com']);

    //     // Test with an empty string as the path
    //     $result = app_url('');
    //     $this->assertEquals('https://example.com', $result);
    // }

    public function test_user_route_with_default_prefix()
    {
        $this->assertEquals('/user', user_route());
        $this->assertEquals('/user/dashboard', user_route('dashboard'));
        $this->assertEquals('/user/dashboard', user_route('/dashboard'));
    }

    // public function test_user_route_with_custom_prefix()
    // {
    //     // Mock the config value for a custom user prefix
    //     config(['coderstm.user_prefix' => '/custom-user']);

    //     // Test with no additional path
    //     $result = user_route();
    //     $this->assertEquals('/custom-user', $result);

    //     // Test with a relative path
    //     $result = user_route('settings');
    //     $this->assertEquals('/custom-user/settings', $result);

    //     // Test with an absolute path
    //     $result = user_route('/settings');
    //     $this->assertEquals('/custom-user/settings', $result);
    // }

    public function test_admin_route_with_default_prefix()
    {
        $this->assertEquals('/admin', admin_route());
        $this->assertEquals('/admin/dashboard', admin_route('dashboard'));
        $this->assertEquals('/admin/dashboard', admin_route('/dashboard'));
    }

    // public function test_admin_route_with_custom_prefix()
    // {
    //     // Mock the config value for a custom admin prefix
    //     config(['coderstm.admin_prefix' => '/custom-admin']);

    //     // Test with no additional path
    //     $result = admin_route();
    //     $this->assertEquals('/custom-admin', $result);

    //     // Test with a relative path
    //     $result = admin_route('settings');
    //     $this->assertEquals('/custom-admin/settings', $result);

    //     // Test with an absolute path
    //     $result = admin_route('/settings');
    //     $this->assertEquals('/custom-admin/settings', $result);
    // }

    public function test_is_active()
    {
        // Simulate a request to 'home'
        $this->get('home');

        // Call the function with a matching route
        $result = is_active('home');

        // Assert the result is 'active'
        $this->assertEquals('active', $result);
    }

    public function test_is_active_returns_empty_for_non_matching_route()
    {
        // Simulate a request to 'dashboard'
        $this->get('dashboard');

        // Call the function with a non-matching route
        $result = is_active('home');

        // Assert the result is an empty string
        $this->assertEquals('', $result);
    }

    public function test_is_active_handles_multiple_routes()
    {
        // Simulate a request to 'about'
        $this->get('about');

        // Call the function with multiple routes
        $result = is_active('home', 'about', 'contact');

        // Assert the result is 'active' for a matching route
        $this->assertEquals('active', $result);

        // Call the function with no matching routes
        $result = is_active('services', 'portfolio');

        // Assert the result is an empty string
        $this->assertEquals('', $result);
    }

    public function test_has_recaptcha()
    {
        Config::set('recaptcha.site_key', 'site_key');
        $this->assertTrue(has_recaptcha());
    }

    public function test_string_to_hex()
    {
        $this->assertEquals('#000d05', string_to_hex('A'));
    }

    public function test_string_to_hsl()
    {
        $this->assertEquals('hsl(65, 35%, 65%)', string_to_hsl('A'));
    }

    public function test_model_log_name()
    {
        $model = new class {
            public $logName = 'Custom Log Name';
        };
        $this->assertEquals('Custom Log Name', model_log_name($model));
    }

    public function test_format_amount()
    {
        $cashierMock = \Mockery::mock('Laravel\Cashier\Cashier');
        $cashierMock->shouldReceive('formatAmount')->with(10000, null, null, [])->andReturn('$100.00');
        $this->assertEquals('$100.00', format_amount(100));
    }

    public function test_currency_symbol()
    {
        $currenciesMock = \Mockery::mock('alias:Symfony\Polyfill\Intl\Icu\Currencies');
        $currenciesMock->shouldReceive('getSymbol')->with('USD')->andReturn('$');
        $this->assertEquals('$', currency_symbol('USD'));
    }

    public function test_get_lang_code()
    {
        $this->assertEquals('en', get_lang_code('en-US'));
    }

    public function test_app_lang()
    {
        $this->assertEquals('en', app_lang());
    }

    public function test_replace_short_code()
    {
        $this->assertEquals('Welcome to AppName', replace_short_code('Welcome to {{APP_NAME}}'));
    }

    public function test_has()
    {
        $this->assertInstanceOf(\Illuminate\Support\Optional::class, has(null));
    }

    public function test_get_country_code()
    {
        $this->assertEquals('*', get_country_code(null));
    }

    public function test_trans_status()
    {
        $this->assertEquals('messages.module.create', trans_status('create', 'module', 'attribute'));
    }

    public function test_trans_module()
    {
        $this->assertEquals('messages.module.create', trans_module('create', 'module'));
    }

    public function test_trans_modules()
    {
        $this->assertEquals('messages.module.create', trans_modules('create', 'module'));
    }

    public function test_trans_attribute()
    {
        $this->assertEquals('messages.key', trans_attribute('key', 'type'));
    }

    public function test_html_text()
    {
        $this->assertEquals('Hello World', html_text('<div>Hello<br>World</div>'));
    }

    public function test_theme()
    {
        $this->assertEquals(theme('path', 'themeName')->toHtml(), '/themes/foundation/path');
        $this->assertEquals(theme('/css/app.css', 'foundation')->toHtml(), '/themes/foundation/css/app.css');
    }
}
