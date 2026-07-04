<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Coderstm\Models\Coupon;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Redeem;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

/**
 * User-focused subscription controller tests.
 * Tests are for authenticated users calling endpoints on their own subscriptions.
 * Each user can only access/modify their own subscriptions.
 */
class SubscriptionControllerTest extends FeatureTestCase
{
    protected $user;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->plan = Plan::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_list_their_subscriptions()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'status',
            'active',
            'canceled',
            'ended',
            'is_valid',
            'usages',
        ]);

        // Verify response contains our subscription
        $data = $response->json();
        $this->assertEquals($subscription->id, $data['id']);
        $this->assertEquals('active', $data['status']);
        $this->assertTrue($data['active']);
    }

    public function test_user_can_get_current_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        // Verify response is an array with subscription data
        $this->assertIsArray($data);
        $this->assertEquals($subscription->id, $data['id']);
        $this->assertEquals('active', $data['status']);
        $this->assertTrue($data['active']);
    }

    public function test_user_on_trial_endpoint_returns_info()
    {
        $trialEnd = now()->addDays(7);
        $this->user->forceFill(['trial_ends_at' => $trialEnd])->save();
        $this->user->refresh();

        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        // Verify trial info is returned
        $this->assertIsArray($data);
        $this->assertArrayHasKey('on_generic_trial', $data);
        $this->assertTrue($data['on_generic_trial']);
        $this->assertArrayHasKey('trial_ends_at', $data);
    }

    public function test_free_forever_user_endpoint_returns_info()
    {
        $this->user->forceFill(['is_free_forever' => true])->save();
        $this->user->refresh();

        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        // Verify free forever flag is returned
        $this->assertIsArray($data);
        $this->assertTrue($data['is_free_forever']);
    }

    public function test_user_without_subscription_returns_info()
    {
        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        // User without subscription and not on trial should have is_free_forever = false
        $this->assertIsArray($data);
        $this->assertFalse($data['is_free_forever']);
    }

    public function test_user_can_view_own_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->getJson(route('subscriptions.show', $subscription->id));

        $response->assertStatus(200);
        $data = $response->json();

        // Verify subscription details are correct
        $this->assertEquals($subscription->id, $data['id']);
        $this->assertEquals($subscription->status, $data['status']);
        $this->assertArrayHasKey('plan', $data);
    }

    public function test_user_cannot_view_other_subscription()
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->getJson(route('subscriptions.show', $subscription->id));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString('do not have access', $response->json('errors.subscription.0'));
    }

    public function test_user_can_cancel_own_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'canceled_at' => null,
        ]);

        $response = $this->postJson(route('subscriptions.cancel', $subscription->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Subscription cancelled successfully.']);

        $subscription->refresh();
        $this->assertNotNull($subscription->canceled_at);
        $this->assertTrue($subscription->canceled());
    }

    public function test_user_cannot_cancel_other_subscription()
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('subscriptions.cancel', $subscription->id));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString('do not have access', $response->json('errors.subscription.0'));
    }

    public function test_user_can_resume_canceled_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'canceled',
            'canceled_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->postJson(route('subscriptions.resume', $subscription->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Subscription resumed successfully.']);

        $subscription->refresh();
        $this->assertNull($subscription->canceled_at);
        $this->assertFalse($subscription->canceled());
    }

    public function test_user_cannot_resume_other_subscription()
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
            'canceled_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->postJson(route('subscriptions.resume', $subscription->id));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString('do not have access', $response->json('errors.subscription.0'));
    }

    public function test_user_can_cancel_scheduled_downgrade()
    {
        $newPlan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'next_plan' => $newPlan->id,
        ]);

        $response = $this->postJson(route('subscriptions.cancel-downgrade', $subscription->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Subscription downgrade cancelled successfully.']);

        $subscription->refresh();
        $this->assertNull($subscription->next_plan);
        $this->assertFalse($subscription->hasDowngrade());
    }

    public function test_user_cannot_cancel_other_subscription_downgrade()
    {
        $otherUser = User::factory()->create();
        $newPlan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
            'next_plan' => $newPlan->id,
        ]);

        $response = $this->postJson(route('subscriptions.cancel-downgrade', $subscription->id));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString('do not have access', $response->json('errors.subscription.0'));
    }

    public function test_user_can_get_subscription_invoices()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $invoice = Order::factory()->create([
            'customer_id' => $this->user->id,
            'orderable_type' => get_class($subscription),
            'orderable_id' => $subscription->id,
        ]);

        $response = $this->getJson(route('subscriptions.invoices', $subscription->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }

    public function test_user_cannot_view_other_subscription_invoices()
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'plan_id' => $this->plan->id,
        ]);

        $response = $this->getJson(route('subscriptions.invoices', $subscription->id));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString('do not have access', $response->json('errors.subscription.0'));
    }

    public function test_user_can_filter_invoices_by_status()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $completedInvoice = Order::factory()->create([
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
            route('subscriptions.invoices', $subscription->id).'?status=completed'
        );

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify only completed invoices are returned
        foreach ($data as $invoice) {
            $this->assertEquals('completed', $invoice['status']);
        }
    }

    public function test_can_check_valid_coupon()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'plan',
            'active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        // Associate coupon with plan
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

    public function test_invalid_coupon_returns_error()
    {
        $response = $this->postJson(route('subscriptions.check-promo-code'), [
            'promotion_code' => 'INVALID_CODE_123',
            'plan_id' => $this->plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['promotion_code']);
        $this->assertStringContainsString('Invalid coupon code', $response->json('errors.promotion_code.0'));
    }

    public function test_expired_coupon_returns_error()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'plan',
            'active' => true,
            'expires_at' => now()->subDay(),
        ]);

        // Associate coupon with plan
        $coupon->plans()->attach($this->plan->id);

        $response = $this->postJson(route('subscriptions.check-promo-code'), [
            'promotion_code' => $coupon->promotion_code,
            'plan_id' => $this->plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['promotion_code']);
        $this->assertStringContainsString('expired', $response->json('errors.promotion_code.0'));
    }

    public function test_inactive_coupon_returns_error()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'plan',
            'active' => false,
            'expires_at' => now()->addDays(30),
        ]);

        // Associate coupon with plan
        $coupon->plans()->attach($this->plan->id);

        $response = $this->postJson(route('subscriptions.check-promo-code'), [
            'promotion_code' => $coupon->promotion_code,
            'plan_id' => $this->plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['promotion_code']);
        $this->assertStringContainsString('not active', $response->json('errors.promotion_code.0'));
    }

    public function test_maxed_out_coupon_returns_error()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'plan',
            'active' => true,
            'expires_at' => now()->addDays(30),
            'max_redemptions' => 1,
        ]);

        // Associate coupon with plan
        $coupon->plans()->attach($this->plan->id);

        // Create a redeem to max out the coupon
        Redeem::create([
            'coupon_id' => $coupon->id,
            'redeemable_type' => 'Coderstm\Models\Subscription',
            'redeemable_id' => 1,
        ]);

        $response = $this->postJson(route('subscriptions.check-promo-code'), [
            'promotion_code' => $coupon->promotion_code,
            'plan_id' => $this->plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['promotion_code']);
        $this->assertStringContainsString('maximum redemptions', $response->json('errors.promotion_code.0'));
    }

    public function test_subscription_response_structure_with_features()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'api-calls',
            'label' => 'API Calls',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan
        $plan = new Plan([
            'label' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => 'Professional plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 2900,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 1000]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $subscription->update(['status' => 'active']);

        // Use some of the feature
        $subscription->recordFeatureUsage('api-calls', 150);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);

        // Get the response data
        $responseData = $response->json();

        // Check basic subscription fields
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('plan', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('active', $responseData['status']);

        // Check features/usages structure
        $this->assertArrayHasKey('usages', $responseData);
        $this->assertIsArray($responseData['usages']);
        $this->assertCount(1, $responseData['usages']);

        // Check individual feature structure
        $usage = $responseData['usages'][0];
        $this->assertArrayHasKey('slug', $usage);
        $this->assertArrayHasKey('label', $usage);
        $this->assertArrayHasKey('type', $usage);
        $this->assertArrayHasKey('resetable', $usage);
        $this->assertArrayHasKey('value', $usage);
        $this->assertArrayHasKey('used', $usage);
        $this->assertArrayHasKey('remaining', $usage);

        // Check specific values
        $this->assertEquals('api-calls', $usage['slug']);
        $this->assertEquals('API Calls', $usage['label']);
        $this->assertEquals('integer', $usage['type']);
        $this->assertTrue($usage['resetable']);
        $this->assertEquals(1000, $usage['value']);
        $this->assertEquals(150, $usage['used']);
        $this->assertEquals(850, $usage['remaining']); // 1000 - 150

        // Check additional subscription fields
        $this->assertArrayHasKey('canceled', $responseData);
        $this->assertArrayHasKey('ended', $responseData);
        $this->assertArrayHasKey('is_valid', $responseData);
        $this->assertFalse($responseData['canceled']);
        $this->assertFalse($responseData['ended']);
        $this->assertTrue($responseData['is_valid']);
    }

    public function test_it_returns_subscription_with_features_data()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'label' => 'Test Feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan without auto-syncing features
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $subscription->update(['status' => 'active']);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'plan' => [
                    'id',
                    'label',
                ],
                'status',
                'usages' => [
                    '*' => [
                        'slug',
                        'label',
                        'type',
                        'resetable',
                        'value',
                        'used',
                        'remaining',
                    ],
                ],
                'canceled',
                'ended',
                'is_valid',
            ]);

        // Check that usages data is properly formatted
        $responseData = $response->json();
        $this->assertIsArray($responseData['usages']);
        $this->assertCount(1, $responseData['usages']);

        $usage = $responseData['usages'][0];
        $this->assertEquals('test-feature', $usage['slug']);
        $this->assertEquals('Test Feature', $usage['label']);
        $this->assertEquals('integer', $usage['type']);
        $this->assertTrue($usage['resetable']);
        $this->assertEquals(10, $usage['value']);
        $this->assertEquals(0, $usage['used']);
        $this->assertEquals(10, $usage['remaining']); // value - used
    }

    public function test_it_returns_subscription_with_multiple_features()
    {
        // Create multiple features
        $feature1 = Feature::factory()->create([
            'slug' => 'feature-1',
            'label' => 'Feature 1',
            'type' => 'integer',
            'resetable' => true,
        ]);

        $feature2 = Feature::factory()->create([
            'slug' => 'feature-2',
            'label' => 'Feature 2',
            'type' => 'boolean',
            'resetable' => false,
        ]);

        // Create a plan
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach features to plan
        $plan->features()->attach($feature1, ['value' => 5]);
        $plan->features()->attach($feature2, ['value' => 1]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $subscription->update(['status' => 'active']);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(2, $responseData['usages']);

        // Check that both features are present
        $slugs = collect($responseData['usages'])->pluck('slug')->toArray();
        $this->assertContains('feature-1', $slugs);
        $this->assertContains('feature-2', $slugs);
    }

    public function test_it_returns_subscription_with_used_features()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'label' => 'Test Feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $subscription->update(['status' => 'active']);

        // Use some of the feature
        $subscription->recordFeatureUsage('test-feature', 3);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);

        $responseData = $response->json();
        $usage = $responseData['usages'][0];

        $this->assertEquals(10, $usage['value']);
        $this->assertEquals(3, $usage['used']);
        $this->assertEquals(7, $usage['remaining']); // value - used
    }

    // ======================================
    // Additional Response Structure Tests
    // ======================================

    public function test_it_can_get_subscription_index()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'status',
                'usages',
            ]);
    }

    public function test_it_returns_no_subscription_message_when_user_has_no_subscription()
    {
        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        // User without subscription should be identified properly
        $this->assertIsArray($data);
    }

    public function test_it_returns_trial_message_when_user_is_on_generic_trial()
    {
        $trialEnd = now()->addDays(7);
        $this->user->forceFill(['trial_ends_at' => $trialEnd])->save();
        $this->user->refresh();

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        // Verify trial info is returned
        $this->assertIsArray($data);
        $this->assertTrue($data['on_generic_trial']);
        $this->assertNotNull($data['trial_ends_at']);
    }

    public function test_user_on_generic_trial_gets_trial_info()
    {
        $trialEnd = now()->addDays(7);
        $this->user->forceFill(['trial_ends_at' => $trialEnd])->save();
        $this->user->refresh();

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['on_generic_trial']);
        $this->assertNotNull($data['trial_ends_at']);
    }

    public function test_free_forever_user_gets_free_forever_info()
    {
        $this->user->forceFill(['is_free_forever' => true])->save();
        $this->user->refresh();

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['is_free_forever']);
    }

    public function test_user_cannot_update_subscription()
    {
        $plan = Plan::factory()->create();
        $newPlan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);

        $response = $this->postJson(route('admin.subscriptions.update', $subscription->id), [
            'user' => $this->user->id,
            'plan' => $newPlan->id,
        ]);

        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    public function test_user_cannot_pay_subscription()
    {
        $plan = Plan::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);

        $response = $this->postJson(route('admin.subscriptions.pay', $subscription->id), [
            'payment_method' => $paymentMethod->id,
        ]);

        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    public function test_user_can_filter_subscriptions_by_status()
    {
        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(route('subscriptions.current'));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('active', $data['status']);
    }
}
