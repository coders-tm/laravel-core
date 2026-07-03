<?php

namespace Tests\Unit;

use App\Models\User;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Coupon;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    #[Test]
    public function it_can_create_a_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $subscription = Subscription::create([
            'type' => 'default',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => Carbon::now(),
        ]);

        $this->assertEquals($user->id, $subscription->user_id);
        $this->assertEquals($plan->id, $subscription->plan_id);

        $subscription->pay(config('stripe.id'));

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    #[Test]
    public function it_can_check_if_subscription_is_active()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $subscription->pay(config('stripe.id'));

        $this->assertTrue($subscription->active());
    }

    #[Test]
    public function it_can_cancel_a_subscription()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
        ]);

        $subscription->pay(config('stripe.id'));

        $subscription = $subscription->cancel();

        $this->assertNotNull($subscription->canceled_at);

        $subscription = $subscription->cancelNow();
        $this->assertEquals(SubscriptionStatus::CANCELED, $subscription->status);
    }

    #[Test]
    public function it_can_resume_a_subscription_within_grace_period()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => Carbon::now()->addDays(5),
        ]);

        $subscription->pay(config('stripe.id'));

        // Cancel the subscription
        $subscription = $subscription->cancel();

        // Resume the subscription
        $subscription = $subscription->resume();

        $this->assertNull($subscription->canceled_at);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    #[Test]
    public function it_cannot_resume_a_subscription_outside_grace_period()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::CANCELED,
            'expires_at' => Carbon::now()->subDays(1),
            'canceled_at' => Carbon::now()->subDays(2),
        ]);

        $this->expectException(\LogicException::class);

        $subscription->resume();
    }

    #[Test]
    public function it_can_check_if_subscription_is_on_trial()
    {
        $subscription = Subscription::factory()->create([
            'trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->assertTrue($subscription->onTrial());
    }

    #[Test]
    public function it_can_extend_the_trial_period()
    {
        $subscription = Subscription::factory()->create([
            'trial_ends_at' => Carbon::now()->addDays(5)->endOfDay(),
        ]);

        $newTrialEndDate = Carbon::now()->addDays(10)->endOfDay();
        $subscription->extendTrial($newTrialEndDate);

        $this->assertTrue($newTrialEndDate->isSameDay($subscription->trial_ends_at));
    }

    #[Test]
    public function it_can_swap_to_a_new_plan()
    {
        $subscription = Subscription::factory()->create();
        $subscription->pay(config('stripe.id'));

        $newPlan = Plan::factory()->create();

        $subscription->swap($newPlan->id);

        $this->assertEquals($newPlan->id, $subscription->plan_id);
    }

    #[Test]
    public function it_can_renew_a_subscription()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => Carbon::now()->subDays(5),
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $subscription->renew();

        $this->assertNull($subscription->canceled_at);

        $subscription->pay(config('stripe.id'));

        $this->assertTrue($subscription->active());
    }

    #[Test]
    public function it_can_apply_a_coupon_to_a_subscription()
    {
        $subscription = Subscription::factory()
            ->for(User::factory(), 'user')
            ->create();
        $coupon = Coupon::factory()->create();

        $subscription->withCoupon($coupon->promotion_code)->save();

        $this->assertEquals($coupon->id, $subscription->coupon_id);
    }
}
