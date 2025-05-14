<?php

namespace Coderstm\Tests\Unit;

use App\Models\User;
use App\Models\Coupon;
use Coderstm\Tests\TestCase;
use Illuminate\Support\Carbon;
use Coderstm\Models\Subscription;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Subscription\Plan;
use PHPUnit\Framework\Attributes\Test;
use Coderstm\Contracts\SubscriptionStatus;

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

        $subscription->pay(PaymentMethod::stripe()->id);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    #[Test]
    public function it_can_check_if_subscription_is_active()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'ends_at' => null,
        ]);

        $subscription->pay(PaymentMethod::stripe()->id);

        $this->assertTrue($subscription->active());
    }

    #[Test]
    public function it_can_cancel_a_subscription()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $subscription->pay(PaymentMethod::stripe()->id);

        $subscription = $subscription->cancel();

        $this->assertNotNull($subscription->ends_at);

        $subscription = $subscription->cancelNow();
        $this->assertEquals(SubscriptionStatus::CANCELED, $subscription->status);
    }

    #[Test]
    public function it_can_resume_a_subscription_within_grace_period()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'ends_at' => Carbon::now()->addDays(5),
        ]);

        $subscription->pay(PaymentMethod::stripe()->id);

        $subscription = $subscription->resume();

        $this->assertNull($subscription->ends_at);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    #[Test]
    public function it_cannot_resume_a_subscription_outside_grace_period()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::CANCELED,
            'ends_at' => Carbon::now()->subDays(1),
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
        $subscription->pay(PaymentMethod::stripe()->id);

        $newPlan = Plan::factory()->create();

        $subscription->swap($newPlan->id);

        $this->assertEquals($newPlan->id, $subscription->plan_id);
    }

    #[Test]
    public function it_can_renew_a_subscription()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => Carbon::now()->subDays(5),
            'status' => SubscriptionStatus::ACTIVE
        ]);

        $subscription->renew();

        $this->assertNull($subscription->ends_at);

        $subscription->pay(PaymentMethod::stripe()->id);

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
