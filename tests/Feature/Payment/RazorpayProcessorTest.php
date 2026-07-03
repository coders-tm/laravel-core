<?php

namespace Tests\Feature\Payment;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Payment\Mappers\RazorpayPayment;
use Coderstm\Payment\Payable;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Error;
use Tests\Feature\FeatureTestCase;

class RazorpayProcessorTest extends FeatureTestCase
{
    use WithFaker;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if Razorpay credentials are not configured
        if (! env('RAZORPAY_API_KEY') || ! env('RAZORPAY_API_SECRET')) {
            $this->markTestSkipped('Razorpay credentials not configured. Set RAZORPAY_API_KEY and RAZORPAY_API_SECRET in phpunit.xml');
        }

        // Get Razorpay payment method from seeder (don't filter by enabled status)
        $paymentMethod = PaymentMethod::byProvider(PaymentMethod::RAZORPAY);

        if (! $paymentMethod) {
            $this->markTestSkipped('Razorpay payment method not found in seeder');
        }

        // Enable the payment method for testing
        $paymentMethod->update([
            'active' => true,
            'test_mode' => true,
            'credentials' => collect([
                ['key' => 'API_KEY', 'value' => env('RAZORPAY_API_KEY'), 'publish' => true],
                ['key' => 'API_SECRET', 'value' => env('RAZORPAY_API_SECRET'), 'publish' => false],
            ]),
        ]);
        PaymentMethod::updateProviderCache(PaymentMethod::RAZORPAY);

        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Helper to convert array to object (for Razorpay API responses)
     */
    protected function arrayToObject(array $data): object
    {
        return json_decode(json_encode($data));
    }

    #[Test]
    public function it_creates_razorpay_client_instance()
    {
        $client = Coderstm::razorpay();

        $this->assertInstanceOf(Api::class, $client);
    }

    #[Test]
    public function it_extracts_card_payment_metadata_from_payment()
    {
        $payment = $this->arrayToObject([
            'id' => 'pay_test123',
            'status' => 'captured',
            'method' => 'card',
            'card' => [
                'last4' => '4242',
                'network' => 'Visa',
                'type' => 'credit',
                'issuer' => 'HDFC Bank',
            ],
            'amount' => 10000,
            'currency' => 'INR',
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 100.00,
        ]);
        $paymentMapper = new RazorpayPayment(
            $payment,
            $this->paymentMethod
        );

        $metadata = $paymentMapper->getMetadata();
        $this->assertEquals('card', $metadata['payment_method_type']);
        $this->assertEquals('4242', $metadata['last_four']);
        $this->assertEquals('Visa', $metadata['card_brand']);
        $this->assertEquals('HDFC Bank', $metadata['issuer']);

        $this->assertEquals('Visa •••• 4242 (HDFC Bank)', $paymentMapper->toString());
    }

    #[Test]
    public function it_extracts_upi_payment_metadata_from_payment()
    {
        $payment = $this->arrayToObject([
            'id' => 'pay_upi123',
            'status' => 'captured',
            'method' => 'upi',
            'vpa' => 'user@paytm',
            'amount' => 5000,
            'currency' => 'INR',
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 50.00,
        ]);
        $paymentMapper = new RazorpayPayment(
            $payment,
            $this->paymentMethod
        );

        $metadata = $paymentMapper->getMetadata();
        $this->assertEquals('upi', $metadata['payment_method_type']);
        $this->assertEquals('user@paytm', $metadata['upi_id']);

        $this->assertEquals('UPI (user@paytm)', $paymentMapper->toString());
    }

    #[Test]
    public function it_extracts_wallet_payment_metadata_from_payment()
    {
        $payment = $this->arrayToObject([
            'id' => 'pay_wallet456',
            'status' => 'captured',
            'method' => 'wallet',
            'wallet' => 'paytm',
            'amount' => 2500,
            'currency' => 'INR',
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 25.00,
        ]);
        $paymentMapper = new RazorpayPayment(
            $payment,
            $this->paymentMethod
        );

        $metadata = $paymentMapper->getMetadata();
        $this->assertEquals('wallet', $metadata['payment_method_type']);
        $this->assertEquals('paytm', $metadata['wallet_type']);

        $this->assertEquals('Paytm Wallet', $paymentMapper->toString());
    }

    #[Test]
    public function it_extracts_netbanking_metadata_from_payment()
    {
        $payment = $this->arrayToObject([
            'id' => 'pay_netbank789',
            'status' => 'captured',
            'method' => 'netbanking',
            'bank' => 'HDFC',
            'amount' => 15000,
            'currency' => 'INR',
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 150.00,
        ]);
        $paymentMapper = new RazorpayPayment(
            $payment,
            $this->paymentMethod
        );

        $metadata = $paymentMapper->getMetadata();
        $this->assertEquals('netbanking', $metadata['payment_method_type']);
        $this->assertEquals('HDFC', $metadata['bank_name']);

        $this->assertEquals('Net Banking (HDFC)', $paymentMapper->toString());
    }

    #[Test]
    public function it_creates_actual_razorpay_order_via_api()
    {
        $api = Coderstm::razorpay();

        // Create a real order via Razorpay API
        $order = $api->order->create([
            'amount' => 50000, // ₹500.00 in paise
            'currency' => 'INR',
            'receipt' => 'test_receipt_'.time(),
            'notes' => [
                'test' => 'live_integration_test',
                'timestamp' => now()->toDateTimeString(),
            ],
        ]);

        // Verify order structure
        $this->assertArrayHasKey('id', $order);
        $this->assertArrayHasKey('amount', $order);
        $this->assertArrayHasKey('currency', $order);
        $this->assertArrayHasKey('status', $order);

        $this->assertEquals(50000, $order['amount']);
        $this->assertEquals('INR', $order['currency']);
        $this->assertEquals('created', $order['status']);
        $this->assertStringStartsWith('order_', $order['id']);

        // Verify we can fetch the order
        $fetchedOrder = $api->order->fetch($order['id']);
        $this->assertEquals($order['id'], $fetchedOrder['id']);
        $this->assertEquals($order['amount'], $fetchedOrder['amount']);
    }

    #[Test]
    public function it_verifies_payment_signature_with_live_data()
    {
        // Create an order first
        $api = Coderstm::razorpay();
        $order = $api->order->create([
            'amount' => 10000,
            'currency' => 'INR',
            'receipt' => 'test_sig_'.time(),
        ]);

        // Generate test payment ID (in production, this comes from Razorpay checkout)
        $testPaymentId = 'pay_test_'.Str::random(14);

        // Generate signature using the actual secret key
        $expectedSignature = hash_hmac(
            'sha256',
            $order['id'].'|'.$testPaymentId,
            env('RAZORPAY_API_SECRET')
        );

        // Verify signature matches
        $attributes = [
            'razorpay_order_id' => $order['id'],
            'razorpay_payment_id' => $testPaymentId,
            'razorpay_signature' => $expectedSignature,
        ];

        try {
            $api->utility->verifyPaymentSignature($attributes);
            $verified = true;
        } catch (\Throwable $e) {
            $verified = false;
        }

        $this->assertTrue($verified, 'Payment signature verification should succeed with correct signature');
    }

    #[Test]
    public function it_creates_orders_with_various_amounts()
    {
        $api = Coderstm::razorpay();
        $amounts = [100, 500, 1000, 5000, 10000]; // Various amounts in paise

        foreach ($amounts as $amount) {
            $order = $api->order->create([
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => 'test_amount_'.$amount.'_'.time(),
            ]);

            $this->assertEquals($amount, $order['amount']);
            $this->assertEquals('created', $order['status']);
            $this->assertStringStartsWith('order_', $order['id']);
        }
    }

    #[Test]
    public function it_handles_invalid_order_fetch_gracefully()
    {
        $api = Coderstm::razorpay();

        $this->expectException(BadRequestError::class);

        // Try to fetch non-existent order
        $api->order->fetch('order_invalid_123456789');
    }

    #[Test]
    public function it_verifies_api_credentials_work()
    {
        // This test will fail if credentials are invalid
        $api = Coderstm::razorpay();

        try {
            // Simple API call to verify credentials work
            $order = $api->order->create([
                'amount' => 100,
                'currency' => 'INR',
                'receipt' => 'test_credentials_'.time(),
            ]);

            $this->assertNotNull($order);
            $credentialsValid = true;
        } catch (Error $e) {
            $credentialsValid = false;
            $this->fail('Invalid Razorpay credentials: '.$e->getMessage());
        }

        $this->assertTrue($credentialsValid);
    }

    #[Test]
    public function it_extracts_metadata_from_actual_api_payment_structure()
    {
        // This test validates that our metadata extractor works with
        // the actual structure returned by Razorpay API

        // Note: We can't create actual payments without completing checkout,
        // but we can verify the structure matches Razorpay's documentation

        $api = Coderstm::razorpay();
        $order = $api->order->create([
            'amount' => 10000,
            'currency' => 'INR',
            'receipt' => 'test_structure_'.time(),
        ]);

        // Verify order has the expected structure for metadata extraction
        $this->assertArrayHasKey('id', $order);
        $this->assertArrayHasKey('amount', $order);
        $this->assertArrayHasKey('currency', $order);
        $this->assertArrayHasKey('receipt', $order);

        // Order should be convertible to array (required for metadata extraction)
        $orderArray = is_array($order) ? $order : $order->toArray();
        $this->assertIsArray($orderArray);
    }

    #[Test]
    public function it_uses_gateway_amount_and_currency_instead_of_notes()
    {
        // Mock a Razorpay payment response that has both direct amount/currency
        // and conflicting values in notes (simulating a scenario where they differ)
        $payment = $this->arrayToObject([
            'id' => 'pay_test_diff_amount',
            'status' => 'captured',
            'method' => 'card',
            'amount' => 5500, // Gateway amount (₹55.00)
            'currency' => 'INR', // Gateway currency
            'notes' => [
                'order_amount' => 50.00, // Original order amount (base currency)
                'order_currency' => 'USD', // Original order currency
            ],
        ]);

        $paymentMapper = new RazorpayPayment(
            $payment,
            $this->paymentMethod
        );

        // Assert mapper uses Gateway values for main properties
        $this->assertEquals(55.00, $paymentMapper->getAmount(), 'Should use gateway amount (5500 / 100)');
        $this->assertEquals('INR', $paymentMapper->getCurrency(), 'Should use gateway currency');

        // Assert AbstractPayment logic automatically captures them in metadata
        $metadata = $paymentMapper->toArray()['metadata'];
        $this->assertEquals(55.00, $metadata['gateway_amount']);
        $this->assertEquals('INR', $metadata['gateway_currency']);

        // Assert we still have access to the original notes if we need them via accessing the response directly in real usage,
        // but here we just want to ensure the Mapper behavior is correct for the payment record.
    }
}
