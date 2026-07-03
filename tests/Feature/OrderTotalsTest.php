<?php

namespace Tests\Feature;

use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderTotalsTest extends TestCase
{
    protected $user;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['provider' => 'manual'],
            [
                'name' => 'Manual Payment',
                'active' => true,
            ]
        );
    }

    #[Test]
    public function paid_total_updates_automatically_when_payment_created()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'grand_total' => 100.00,
        ]);

        $this->assertEquals(0.00, $order->paid_total);

        // Create payment
        $order->payments()->create([
            'amount' => 50.00,
            'status' => 'completed',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        // Check that paid_total was auto-updated
        $this->assertEquals(50.00, $order->fresh()->paid_total);

        // Add another payment
        $order->payments()->create([
            'amount' => 25.00,
            'status' => 'completed',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        // Check total is sum of all payments
        $this->assertEquals(75.00, $order->fresh()->paid_total);
    }

    #[Test]
    public function refund_total_updates_automatically_when_refund_created()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'grand_total' => 100.00,
        ]);

        // Create payment first
        $order->payments()->create([
            'amount' => 100.00,
            'status' => 'completed',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertEquals(0.00, $order->fresh()->refund_total);

        // Create refund
        $order->refunds()->create([
            'amount' => 30.00,
            'reason' => 'Customer request',
        ]);

        // Check that refund_total was auto-updated
        $this->assertEquals(30.00, $order->fresh()->refund_total);

        // Add another refund
        $order->refunds()->create([
            'amount' => 20.00,
            'reason' => 'Defective item',
        ]);

        // Check total is sum of all refunds
        $this->assertEquals(50.00, $order->fresh()->refund_total);
    }

    #[Test]
    public function refundable_amount_calculated_correctly()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'grand_total' => 100.00,
        ]);

        // Create payment
        $order->payments()->create([
            'amount' => 100.00,
            'status' => 'completed',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $order = $order->fresh();
        $this->assertEquals(100.00, $order->refundable_amount);

        // Add refund
        $order->refunds()->create([
            'amount' => 40.00,
            'reason' => 'Partial refund',
        ]);

        $order = $order->fresh();
        $this->assertEquals(60.00, $order->refundable_amount);
    }
}
