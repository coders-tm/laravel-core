<?php

namespace Tests\Feature\Subscription;

use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CheckSubscribedMiddlewareTest extends TestCase
{
    use RefreshDatabase, WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    protected function defineRoutes($router)
    {
        $router->get('/test-subscribed', function () {
            return response()->json(['message' => 'You are subscribed!']);
        })->middleware(['subscribed', 'api']);
    }

    #[Test]
    public function it_allows_subscribed_users()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
        ]);

        $this->assertEquals('http://localhost', config('app.url'));

        $subscription->pay(config('stripe.id'));

        Sanctum::actingAs($subscription->user);

        $this->getJson('/test-subscribed')
            ->assertOk()
            ->assertJson(['message' => 'You are subscribed!']);
    }

    #[Test]
    public function it_blocks_unsubscribed_users()
    {
        $plan = Plan::factory()->create(['price' => 1000]);
        $subscription = Subscription::factory()->create([
            'trial_ends_at' => null,
            'plan_id' => $plan->id,
        ]);

        $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
        ]);

        $this->actingAs($subscription->user, 'sanctum');

        $response = $this->get('/test-subscribed');

        $response->assertStatus(403);
        $response->assertJson(['subscribed' => false, 'message' => trans('messages.subscription.none')]);
    }

    #[Test]
    public function it_not_blocked_when_active_canceled_subscriptions()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
            'starts_at' => now(),
            'expires_at' => now()->addDays(10),
        ]);

        $subscription->pay(config('stripe.id'));

        $subscription = $subscription->cancel();

        $this->actingAs($subscription->user, 'sanctum');

        $response = $this->get('/test-subscribed');

        $response->assertStatus(200);
        // $response->assertJson(['cancelled' => true, 'message' => trans('messages.subscription.canceled', ['date' => $subscription->expires_at->format('d M, Y')])]);
    }

    #[Test]
    public function it_blocks_canceled_subscriptions()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
        ]);

        $subscription->pay(config('stripe.id'));

        $subscription = $subscription->cancelNow();

        $this->actingAs($subscription->user, 'sanctum');

        $response = $this->get('/test-subscribed');

        $response->assertStatus(403);
        $response->assertJson(['subscribed' => false, 'message' => trans('messages.subscription.none')]);
    }
}
