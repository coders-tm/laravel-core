<?php

namespace Coderstm\Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Coderstm\Models\Subscription;
use Orchestra\Testbench\TestCase;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Subscription\Plan;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckSubscribedMiddlewareTest extends TestCase
{
    use WithWorkbench, RefreshDatabase;

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

    /** @test */
    public function it_allows_subscribed_users()
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->assertEquals('http://localhost', config('app.url'));

        $subscription->pay(PaymentMethod::stripe()->id);

        Sanctum::actingAs($subscription->user);

        $this->getJson('/test-subscribed')
            ->assertOk()
            ->assertJson(['message' => 'You are subscribed!']);
    }

    /** @test */
    public function it_blocks_unsubscribed_users()
    {
        $plan = Plan::factory()->create(['price' => 1000]);
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'ends_at' => null,
            'plan_id' => $plan->id,
        ]);

        $this->actingAs($subscription->user);

        $response = $this->get('/test-subscribed');

        $response->assertStatus(403);
        $response->assertJson(['subscribed' => false, 'message' => trans('messages.subscription.none')]);
    }

    /** @test */
    public function it_not_blocked_when_active_canceled_subscriptions()
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $subscription->pay(PaymentMethod::stripe()->id);

        $subscription = $subscription->cancel();

        $this->actingAs($subscription->user);

        $response = $this->get('/test-subscribed');

        $response->assertStatus(200);
        $response->assertJson(['cancelled' => true, 'message' => trans('messages.subscription.canceled', ['date' => $subscription->ends_at->format('d M, Y')])]);
    }

    /** @test */
    public function it_blocks_canceled_subscriptions()
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $subscription->pay(PaymentMethod::stripe()->id);

        $subscription = $subscription->cancelNow();

        $this->actingAs($subscription->user);

        $response = $this->get('/test-subscribed');

        $response->assertStatus(403);
        $response->assertJson(['subscribed' => false, 'message' => trans('messages.subscription.none')]);
    }
}
