<?php

namespace Tests\Feature\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\FeatureTestCase;

class CancelEndpointCancelsOpenInvoicesTest extends FeatureTestCase
{
    protected function createPlan(): Plan
    {
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

        return $plan;
    }

    public function test_cancel_endpoint_cancels_open_invoices_before_subscription()
    {
        $plan = $this->createPlan();
        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'status' => Subscription::EXPIRED,
        ]);

        $order = new Order([
            'customer_id' => $subscription->user_id,
            'orderable_id' => $subscription->id,
            // Use configured subscription model for correct morph type resolution
            'orderable_type' => Coderstm::$subscriptionModel,
            'currency' => config('app.currency'),
            'collect_tax' => false,
            'source' => 'Membership',
            'sub_total' => 1000,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 1000,
            'status' => Order::STATUS_OPEN,
            'fulfillment_status' => Order::STATUS_UNFULFILLED,
        ]);
        $order->save();

        // Authenticate as the subscription owner
        Sanctum::actingAs($subscription->user);

        $response = $this->postJson(route('subscriptions.cancel', [
            'subscription' => $subscription->id,
        ]));
        $response->assertStatus(200);

        $this->assertTrue($order->fresh()->is_cancelled);
    }
}
