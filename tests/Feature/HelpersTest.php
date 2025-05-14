<?php

namespace Coderstm\Tests\Feature;

use App\Models\User;
use Coderstm\Models\Tax;
use Coderstm\Models\Admin;
use Laravel\Sanctum\Sanctum;
use Coderstm\Models\AppSetting;
use Coderstm\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\Notification;
use Coderstm\Notifications\NewAdminNotification;
use Illuminate\Notifications\AnonymousNotifiable;

class HelpersTest extends FeatureTestCase
{
    protected function defineRoutes($router)
    {
        $router->get('/foo', function () {
            if (is_user() || is_admin()) {
                return guard();
            }

            return response(403);
        })->middleware('auth:sanctum');

        $router->get('/email', function () {
            return user('email');
        })->middleware('auth:sanctum');
    }


    public function test_guard_function_returns_user_guard()
    {
        // Create the user
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Make the request to the route
        $this->get('/foo')
            ->assertStatus(200)
            ->assertSee('users');
    }

    public function test_is_admin_function_returns_true_for_admin_guard()
    {
        // Create the user
        $user = Admin::factory()->create();

        Sanctum::actingAs($user);

        // Make the request to the route
        $this->get('/foo')
            ->assertStatus(200)
            ->assertSee('admins');
    }

    public function test_user_function_returns_specific_user_property()
    {
        // Create the user
        $user = Admin::factory()->create();

        Sanctum::actingAs($user);

        // Make the request to the route
        $this->get('/email')
            ->assertStatus(200)
            ->assertSee($user->email);
    }

    public function test_company_address()
    {
        AppSetting::updateOrInsert([
            'key' => 'address'
        ], [
            'key' => 'address',
            'options' => json_encode([
                'company' => 'Company',
                'line1' => 'Line 1',
                'line2' => 'Line 2',
                'city' => 'City',
                'state' => 'State',
                'postal_code' => '12345',
                'country' => 'Country'
            ])
        ]);

        $this->assertEquals('Company, Line 1, Line 2, City, State, 12345, Country', company_address());
        $this->assertEquals('Company<br>Line 1<br>Line 2<br>City<br>State, 12345<br>Country', company_address(true));
    }

    public function test_opening_times()
    {
        AppSetting::updateOrInsert([
            'key' => 'opening-times'
        ], [
            'key' => 'opening-times',
            'options' => json_encode([
                ['name' => 'Monday'],
                ['name' => 'Tuesday']
            ])
        ]);

        $this->assertCount(2, opening_times());
    }

    public function test_settings()
    {
        AppSetting::updateValue('config', ['name' => 'value']);
        $this->assertEquals('value', settings('config.name'));
    }

    public function test_app_settings()
    {
        $settings = AppSetting::updateValue('foo', ['bar' => 'baz']);

        $this->assertEquals($settings, settings('foo'));
    }

    public function test_admin_notify()
    {
        Notification::fake();

        $admin = Admin::factory()->create();

        admin_notify(new NewAdminNotification($admin, 'password'));

        Notification::assertSentTo(
            new AnonymousNotifiable(),
            NewAdminNotification::class,
            function ($notification, $channels) {
                return get_class($notification) === NewAdminNotification::class;
            }
        );
    }

    public function test_country_taxes()
    {
        Tax::create([
            'country' => 'United States',
            'label' => 'VAT',
            'code' => 'US',
            'state' => '*',
            'rate' => 10,
            'priority' => 0,
        ]);

        Tax::create([
            'country' => 'United States',
            'label' => 'VAT',
            'code' => 'US',
            'state' => 'California',
            'rate' => 15,
            'priority' => 1,
        ]);

        $this->assertNotEmpty(country_taxes('US'));
        $this->assertNotEmpty(country_taxes('US', 'California'));
    }

    public function test_default_tax()
    {
        Tax::create([
            'country' => 'United Kingdom',
            'label' => 'VAT',
            'code' => 'UK',
            'state' => '*',
            'rate' => 10,
            'priority' => 0,
        ]);

        Tax::create([
            'country' => 'United Kingdom',
            'label' => 'VAT',
            'code' => 'UK',
            'state' => 'England',
            'rate' => 15,
            'priority' => 0,
        ]);

        $this->assertNotEmpty(default_tax());
    }

    public function test_rest_of_world_tax()
    {
        $this->assertNotEmpty(rest_of_world_tax());
    }

    public function test_billing_address_tax()
    {
        $this->assertNotEmpty(billing_address_tax(['country' => 'United States']));
        $this->assertNotEmpty(billing_address_tax(['country' => 'Canada']));
    }
}
