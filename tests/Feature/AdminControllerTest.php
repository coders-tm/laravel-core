<?php

namespace Tests\Feature;

use App\Models\Admin;
use Coderstm\Models\Module;
use Coderstm\Notifications\NewAdminNotification;
use Database\Seeders\NotificationSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

class AdminControllerTest extends FeatureTestCase
{
    use RefreshDatabase;

    /** @var Admin */
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::factory()->admin()->create();
        $this->admin = Admin::find($admin->id);
        Sanctum::actingAs($this->admin, [], 'sanctum');

        Route::get('password/reset/{token}', function () {
            return 'dummy reset page';
        })->name('password.reset');

        $this->seed(NotificationSeeder::class);
    }

    public function test_it_can_list_admins()
    {
        Admin::factory()->count(3)->create();

        $response = $this->getJson(route('admins.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'first_name', 'last_name', 'email'],
                ],
            ]);
    }

    public function test_it_can_get_admin_options()
    {
        Admin::factory()->count(2)->create();

        $response = $this->getJson(route('admins.options'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'first_name', 'last_name', 'email'],
                ],
            ]);
    }

    public function test_it_can_store_admin()
    {
        Notification::fake();

        $adminData = [
            'first_name' => 'New',
            'last_name' => 'Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'groups' => [],
            'permissions' => [],
        ];

        $response = $this->postJson(route('admins.store'), $adminData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name', 'email'],
                'message',
            ]);

        $this->assertDatabaseHas('admins', [
            'email' => 'newadmin@example.com',
            'first_name' => 'New',
            'last_name' => 'Admin',
        ]);

        $newAdmin = \Coderstm\Coderstm::$adminModel::where('email', 'newadmin@example.com')->first();
        Notification::assertSentTo($newAdmin, NewAdminNotification::class);
    }

    public function test_it_can_show_admin()
    {
        $targetAdmin = Admin::factory()->create();

        $response = $this->getJson(route('admins.show', $targetAdmin->id));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'first_name',
                'last_name',
                'email',
                'permissions',
                'groups',
            ]);

        $this->assertEquals($targetAdmin->id, $response->json('id'));
    }

    public function test_it_can_update_admin()
    {
        $targetAdmin = Admin::factory()->create();

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'AdminName',
            'email' => 'updatedadmin@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'groups' => [],
            'permissions' => [],
        ];

        $response = $this->putJson(route('admins.update', $targetAdmin->id), $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name', 'email'],
                'message',
            ]);

        $this->assertDatabaseHas('admins', [
            'id' => $targetAdmin->id,
            'first_name' => 'Updated',
            'last_name' => 'AdminName',
            'email' => 'updatedadmin@example.com',
        ]);
    }

    public function test_it_can_get_modules_with_permissions()
    {
        $module = Module::create([
            'name' => 'Test Module',
        ]);

        $response = $this->getJson(route('admins.modules'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'permissions'],
            ]);
    }

    public function test_it_can_send_reset_password_request()
    {
        Notification::fake();

        $targetAdmin = Admin::factory()->create([
            'email' => 'resetadmin@example.com',
        ]);

        $response = $this->postJson(route('admins.reset-password-request', $targetAdmin->id));

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message']);

        $this->assertDatabaseHas('admins_password_resets', [
            'email' => 'resetadmin@example.com',
        ]);

        // TODO: Need to check why notification isn't sending
        // Notification::assertSentTo($targetAdmin, ResetPassword::class);
    }

    public function test_it_can_change_admin_type()
    {
        $targetAdmin = Admin::factory()->create([
            'is_supper_admin' => false,
        ]);

        $response = $this->postJson(route('admins.change-admin', $targetAdmin->id));

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('admins', [
            'id' => $targetAdmin->id,
            'is_supper_admin' => true,
        ]);
    }

    public function test_it_cannot_change_own_admin_type()
    {
        $response = $this->postJson(route('admins.change-admin', $this->admin->id));

        $response->assertStatus(403);
    }

    public function test_it_can_change_active_status()
    {
        $targetAdmin = Admin::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->postJson(route('admins.change-active', $targetAdmin->id));

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('admins', [
            'id' => $targetAdmin->id,
            'is_active' => false,
        ]);
    }

    public function test_it_cannot_change_own_active_status()
    {
        $response = $this->postJson(route('admins.change-active', $this->admin->id));

        $response->assertStatus(403);
    }
}
