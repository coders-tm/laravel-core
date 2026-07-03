<?php

namespace Tests\Feature\Subscription;

use App\Models\Admin;
use App\Models\User;
use Coderstm\Models\Coupon;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

class AdminSubscriptionControllerTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $user;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->plan = Plan::factory()->create();

        Sanctum::actingAs($this->admin);
    }

    public function test_admin_can_get_all_subscriptions()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->getJson(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'active', 'plan', 'user'],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_admin_can_filter_subscriptions_by_user()
    {
        $otherUser = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->getJson(
            route('admin.subscriptions.index', ['user' => $this->user->id])
        );

        $response->assertStatus(200);
        $data = $response->json('data');

        // All returned subscriptions should belong to the specified user
        foreach ($data as $subscription) {
            $this->assertEquals($this->user->id, $subscription['user']['id']);
        }
    }

    public function test_admin_can_filter_subscriptions_by_status()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'canceled',
        ]);

        $response = $this->getJson(
            route('admin.subscriptions.index', ['status' => 'active'])
        );

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $subscription) {
            $this->assertEquals('active', $subscription['status']);
        }
    }

    public function test_admin_can_get_user_current_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            route('admin.subscriptions.current', ['user' => $this->user->id])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'status', 'plan']);
        $this->assertEquals($subscription->id, $response->json('id'));
    }

    public function test_admin_can_view_any_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->getJson(route('admin.subscriptions.show', $subscription->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'status',
            'plan',
            'user',
        ]);
        $this->assertEquals($subscription->id, $response->json('id'));
        $this->assertEquals($this->user->id, $response->json('user.id'));
    }

    public function test_admin_can_create_subscription()
    {
        $response = $this->postJson(route('admin.subscriptions.store'), [
            'user' => $this->user->id,
            'plan' => $this->plan->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'status', 'plan'],
        ]);

        // Verify subscription was created
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);
    }

    public function test_admin_can_create_subscription_with_custom_dates()
    {
        $startsAt = now()->addDays(1);
        $expiresAt = now()->addDays(31);

        $response = $this->postJson(route('admin.subscriptions.store'), [
            'user' => $this->user->id,
            'plan' => $this->plan->id,
            'starts_at' => $startsAt->toDateTimeString(),
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);

        $response->assertStatus(201);

        $subscription = Subscription::latest()->first();
        // Verify subscription was created with correct expiration date
        $this->assertNotNull($subscription->expires_at);
        $this->assertTrue($subscription->expires_at->isAfter(now()));
    }

    public function test_admin_can_create_subscription_with_trial()
    {
        $response = $this->postJson(route('admin.subscriptions.store'), [
            'user' => $this->user->id,
            'plan' => $this->plan->id,
            'trial_days' => 14,
        ]);

        $response->assertStatus(201);

        $subscription = Subscription::latest()->first();
        $this->assertNotNull($subscription->trial_ends_at);
    }

    public function test_admin_can_mark_subscription_as_paid_on_creation()
    {
        $paymentMethod = PaymentMethod::first() ?? PaymentMethod::factory()->create();

        $response = $this->postJson(route('admin.subscriptions.store'), [
            'user' => $this->user->id,
            'plan' => $this->plan->id,
            'mark_as_paid' => true,
            'payment_method' => $paymentMethod->id,
        ]);

        $response->assertStatus(201);

        $subscription = Subscription::latest()->first();
        // Verify subscription was created successfully
        $this->assertNotNull($subscription);
        $this->assertIsInt($subscription->id);
    }

    public function test_admin_can_cancel_user_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('admin.subscriptions.cancel', $subscription->id));

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_admin_can_resume_user_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'canceled_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->postJson(route('admin.subscriptions.resume', $subscription->id));

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertNull($subscription->canceled_at);
        $this->assertEquals('active', $subscription->status);
    }

    public function test_admin_can_cancel_subscription_downgrade()
    {
        $newPlan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'next_plan' => $newPlan->id,
        ]);

        $response = $this->postJson(route('admin.subscriptions.cancel-downgrade', $subscription->id));

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertNull($subscription->next_plan);
    }

    public function test_admin_can_get_subscription_invoices()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        Order::factory()->create([
            'customer_id' => $this->user->id,
            'orderable_type' => get_class($subscription),
            'orderable_id' => $subscription->id,
        ]);

        $response = $this->getJson(route('admin.subscriptions.invoices', $subscription->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }

    public function test_admin_can_filter_subscription_invoices_by_status()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        Order::factory()->create([
            'customer_id' => $this->user->id,
            'orderable_type' => get_class($subscription),
            'orderable_id' => $subscription->id,
            'status' => 'completed',
        ]);

        Order::factory()->create([
            'customer_id' => $this->user->id,
            'orderable_type' => get_class($subscription),
            'orderable_id' => $subscription->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson(
            route('admin.subscriptions.invoices', $subscription->id)
                .'?status=completed'
        );

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $invoice) {
            $this->assertEquals('completed', $invoice['status']);
        }
    }

    public function test_admin_can_check_promo_code()
    {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        // Attach coupon to plan
        $coupon->plans()->attach($this->plan->id);

        $response = $this->postJson(route('subscriptions.check-promo-code'), [
            'promotion_code' => $coupon->promotion_code,
            'plan_id' => $this->plan->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'promotion_code',
                'name',
                'discount_type',
                'value',
                'duration',
            ]);
    }
}
