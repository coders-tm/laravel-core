<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Coderstm\Events\ResetFeatureUsages;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class YearlyBillingTest extends TestCase
{
    public function test_plan_can_store_yearly_fee()
    {
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
        ]);

        $this->assertEquals(1000, $plan->price);
        $this->assertEquals(10000, $plan->yearly_fee);
    }

    public function test_plan_yearly_price_formatted_accessor()
    {
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
        ]);

        $this->assertNotNull($plan->yearly_price_formatted);
        $this->assertStringContainsString('$10,000.00', $plan->yearly_price_formatted);
    }

    public function test_plan_yearly_fee_is_calculated_from_price_when_no_yearly_fee_set()
    {
        $plan = Plan::factory()->create([
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
            'yearly_fee' => null,
        ]);

        $this->assertEquals(1000 * 12, $plan->yearly_fee);
    }

    public function test_plan_is_free_checks_yearly_fee()
    {
        $plan = Plan::factory()->create([
            'price' => 0,
            'yearly_fee' => 100,
        ]);

        $this->assertFalse($plan->isFree());
    }

    public function test_plan_is_free_when_both_prices_zero()
    {
        $plan = Plan::factory()->create([
            'price' => 0,
            'yearly_fee' => 0,
        ]);

        $this->assertTrue($plan->isFree());
    }

    public function test_plan_is_free_when_yearly_fee_null_and_price_zero()
    {
        $plan = Plan::factory()->create([
            'price' => 0,
            'yearly_fee' => null,
        ]);

        $this->assertTrue($plan->isFree());
    }

    public function test_yearly_fee_is_included_in_currency_fields()
    {
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
        ]);

        $this->assertContains('yearly_fee', $plan->getCurrencyFields());
    }

    public function test_new_subscription_with_yearly_billing_sets_correct_interval()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertEquals('year', $subscription->billing_interval);
        $this->assertEquals(1, $subscription->billing_interval_count);
    }

    public function test_new_subscription_with_yearly_billing_sets_credit_resets_at()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertNotNull($subscription->credit_resets_at);
        $this->assertTrue($subscription->credit_resets_at->isFuture());
    }

    public function test_new_subscription_with_yearly_billing_credit_resets_at_matches_plan_interval()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $expectedCreditReset = Carbon::parse('2026-07-01 12:00:00');
        $this->assertTrue(
            $subscription->credit_resets_at->eq($expectedCreditReset),
            "Expected {$expectedCreditReset} but got {$subscription->credit_resets_at}"
        );

        Carbon::setTestNow();
    }

    public function test_new_subscription_with_monthly_billing_sets_credit_resets_at()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'monthly');
        $subscription->save();

        $this->assertNotNull($subscription->credit_resets_at);
    }

    public function test_new_subscription_monthly_billing_uses_plan_interval()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'interval' => 'month',
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'monthly');
        $subscription->save();

        $this->assertEquals('month', $subscription->billing_interval);
    }

    public function test_yearly_subscription_expires_at_is_one_year_from_now()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertTrue(
            $subscription->expires_at->eq(Carbon::parse('2027-06-01 12:00:00')),
            "Expected 2027-06-01 12:00:00 but got {$subscription->expires_at}"
        );

        Carbon::setTestNow();
    }

    public function test_yearly_subscription_upcoming_invoice_uses_yearly_fee()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $invoice = $subscription->upcomingInvoice(true);

        $this->assertNotNull($invoice);
        $this->assertCount(1, $invoice->line_items);
        $this->assertEquals(10000, $invoice->line_items[0]['price']);
        $this->assertEquals(10000, $invoice->line_items[0]['total']);
    }

    public function test_yearly_subscription_upcoming_invoice_uses_year_interval()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $invoice = $subscription->upcomingInvoice(true);

        $this->assertNotNull($invoice);
        $this->assertStringContainsString('year', $invoice->line_items[0]['title']);
    }

    public function test_monthly_subscription_upcoming_invoice_uses_plan_price()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'monthly');
        $subscription->save();

        $invoice = $subscription->upcomingInvoice(true);

        $this->assertNotNull($invoice);
        $this->assertEquals(1000, $invoice->line_items[0]['price']);
        $this->assertStringContainsString('month', $invoice->line_items[0]['title']);
    }

    public function test_yearly_subscription_to_response_includes_credit_resets_at()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $response = $subscription->toResponse();

        $this->assertArrayHasKey('credit_resets_at', $response);
        $this->assertNotNull($response['credit_resets_at']);
    }

    public function test_monthly_subscription_to_response_credit_resets_at_is_not_null()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'monthly');
        $subscription->save();

        $response = $subscription->toResponse();

        $this->assertArrayHasKey('credit_resets_at', $response);
        $this->assertNotNull($response['credit_resets_at']);
    }

    public function test_advance_credit_resets_at_moves_to_next_plan_interval()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertTrue(
            $subscription->credit_resets_at->eq(Carbon::parse('2026-07-01 12:00:00'))
        );

        $subscription->advanceCreditResetsAt();

        $this->assertTrue(
            $subscription->credit_resets_at->eq(Carbon::parse('2026-08-01 12:00:00')),
            "Expected 2026-08-01 12:00:00 but got {$subscription->credit_resets_at}"
        );

        Carbon::setTestNow();
    }

    public function test_swap_resets_to_plan_defaults_and_updates_credit_resets_at()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);
        $newPlan = Plan::factory()->create([
            'price' => 2000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();
        $subscription->paymentConfirmation();

        $this->assertEquals('year', $subscription->billing_interval);
        $this->assertNotNull($subscription->credit_resets_at);

        $subscription->swap($newPlan->id, 'monthly', false);

        $this->assertEquals($newPlan->id, $subscription->plan_id);
        $this->assertEquals('month', $subscription->billing_interval);
        $this->assertNotNull($subscription->credit_resets_at);
    }

    public function test_reset_usages_command_resets_credit_resets_at_subscriptions()
    {
        Event::fake();

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'label' => 'Yearly Test',
            'slug' => 'yearly-test',
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();
        $subscription->paymentConfirmation();

        $this->assertTrue($subscription->credit_resets_at->isFuture());

        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));

        $this->artisan('coderstm:subscriptions-reset-usages')
            ->expectsOutputToContain("Credit usage of subscription #{$subscription->id}")
            ->assertExitCode(0);

        $subscription->refresh();

        $this->assertTrue(
            $subscription->credit_resets_at->eq(Carbon::parse('2026-08-15 12:00:00')),
            "Expected 2026-08-15 12:00:00 but got {$subscription->credit_resets_at}"
        );

        Event::assertDispatched(ResetFeatureUsages::class);

        Carbon::setTestNow();
    }

    public function test_reset_usages_command_resets_monthly_subscriptions_via_credit_resets_at()
    {
        Event::fake();

        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'label' => 'Monthly Test',
            'slug' => 'monthly-test',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'monthly');
        $subscription->save();
        $subscription->paymentConfirmation();

        $this->assertNotNull($subscription->credit_resets_at);

        Carbon::setTestNow(Carbon::parse('2026-07-02 12:00:00'));

        $this->artisan('coderstm:subscriptions-reset-usages')
            ->expectsOutputToContain("Usages of subscription #{$subscription->id} has been reset!")
            ->assertExitCode(0);

        Carbon::setTestNow();
    }

    public function test_renew_advances_credit_resets_at()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
            'grace_period_days' => 7,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();
        $subscription->paymentConfirmation();

        $originalCreditReset = $subscription->credit_resets_at->copy();

        Carbon::setTestNow(Carbon::parse('2027-06-01 12:00:00'));

        $subscription->expires_at = Carbon::now()->subDay();
        $subscription->save();

        $subscription->renew();

        $this->assertNotNull($subscription->credit_resets_at);
        $this->assertTrue(
            $subscription->credit_resets_at->gt($originalCreditReset),
            'credit_resets_at should have advanced after renewal'
        );

        Carbon::setTestNow();
    }

    public function test_yearly_billing_creates_subscription_with_correct_period()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertTrue(
            $subscription->starts_at->eq(Carbon::parse('2026-06-01 12:00:00')),
            "starts_at: expected 2026-06-01 12:00:00 got {$subscription->starts_at}"
        );
        $this->assertTrue(
            $subscription->expires_at->eq(Carbon::parse('2027-06-01 12:00:00')),
            "expires_at: expected 2027-06-01 12:00:00 got {$subscription->expires_at}"
        );
        $this->assertEquals('year', $subscription->billing_interval);
        $this->assertEquals(1, $subscription->billing_interval_count);
        $this->assertTrue(
            $subscription->credit_resets_at->eq(Carbon::parse('2026-07-01 12:00:00')),
            "credit_resets_at: expected 2026-07-01 12:00:00 got {$subscription->credit_resets_at}"
        );

        Carbon::setTestNow();
    }

    public function test_yearly_subscription_generates_invoice_with_yearly_price()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 12000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->saveAndInvoice([], true);

        $this->assertNotNull($subscription->latestInvoice);
        $invoice = $subscription->latestInvoice;

        $this->assertEquals(12000, $invoice->line_items[0]['price']);
        $this->assertEquals(12000, $invoice->sub_total);
    }

    public function test_monthly_subscription_generates_invoice_with_monthly_price()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 12000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'monthly');
        $subscription->saveAndInvoice([], true);

        $this->assertNotNull($subscription->latestInvoice);
        $invoice = $subscription->latestInvoice;

        $this->assertEquals(1000, $invoice->line_items[0]['price']);
        $this->assertEquals(1000, $invoice->sub_total);
    }

    public function test_credit_resets_at_not_set_when_plan_has_no_yearly_fee()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => null,
            'interval' => 'month',
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertEquals('year', $subscription->billing_interval);
        $this->assertNotNull(
            $subscription->credit_resets_at,
            'credit_resets_at should be set for yearly billing even without yearly_fee'
        );
    }

    public function test_yearly_billing_with_trial_sets_credit_resets_at_correctly()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 14,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->save();

        $this->assertEquals('year', $subscription->billing_interval);
        $this->assertTrue($subscription->onTrial());
        $this->assertNotNull($subscription->credit_resets_at);
        $this->assertTrue(
            $subscription->credit_resets_at->eq(Carbon::parse('2026-07-15 12:00:00')),
            "Expected 2026-07-15 12:00:00 but got {$subscription->credit_resets_at}"
        );

        Carbon::setTestNow();
    }

    public function test_payment_keeps_credit_resets_at_intact()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'price' => 1000,
            'yearly_fee' => 10000,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_days' => 0,
        ]);

        $subscription = $user->newSubscription('default', $plan, 'yearly');
        $subscription->saveAndInvoice([], true);

        $this->assertNotNull($subscription->credit_resets_at);

        $originalCreditReset = $subscription->credit_resets_at->copy();

        $subscription->paymentConfirmation();

        $subscription->refresh();

        $this->assertNotNull($subscription->credit_resets_at);
        $this->assertTrue(
            $subscription->credit_resets_at->eq($originalCreditReset),
            'paymentConfirmation should preserve credit_resets_at'
        );

        Carbon::setTestNow();
    }
}
