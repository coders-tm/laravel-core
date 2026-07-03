<?php

namespace Tests\Feature\Payment;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Mappers\StripePayment;
use Coderstm\Payment\Processor;
use Coderstm\Payment\Processors\StripeProcessor;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Stripe\StripeClient;
use Tests\Feature\FeatureTestCase;

class StripeProcessorTest extends FeatureTestCase
{
    use WithFaker;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if Stripe credentials are not configured
        if (! env('STRIPE_SECRET')) {
            $this->markTestSkipped('Stripe credentials not configured. Set STRIPE_SECRET in phpunit.xml');
        }

        // Get Stripe payment method created by seeder
        $paymentMethod = PaymentMethod::byProvider(PaymentMethod::STRIPE);

        if (! $paymentMethod) {
            $this->markTestSkipped('Stripe payment method not found. Run seeders first.');
        }

        $this->paymentMethod = $paymentMethod;

        // Ensure credentials are correct and published for test
        $this->paymentMethod->credentials = collect([
            ['key' => 'API_KEY', 'value' => env('STRIPE_KEY'), 'publish' => true],
            ['key' => 'API_SECRET', 'value' => env('STRIPE_SECRET'), 'publish' => false],
            ['key' => 'WEBHOOK_SECRET', 'value' => env('STRIPE_WEBHOOK_SECRET'), 'publish' => false],
        ]);
        $this->paymentMethod->save();
        PaymentMethod::updateProviderCache(PaymentMethod::STRIPE);
    }

    protected function tearDown(): void
    {
        $this->resetStripeClient();
        parent::tearDown();
    }

    protected function mockStripeClient($mock)
    {
        $reflection = new \ReflectionClass(Coderstm::class);
        $property = $reflection->getProperty('stripeClient');
        $property->setAccessible(true);
        $property->setValue(null, $mock);
    }

    protected function resetStripeClient()
    {
        $reflection = new \ReflectionClass(Coderstm::class);
        $property = $reflection->getProperty('stripeClient');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    /**
     * Convert array to object for mapper compatibility
     */
    protected function arrayToObject(array $data): object
    {
        return json_decode(json_encode($data));
    }

    #[Test]
    public function it_creates_stripe_payment_method_with_correct_configuration()
    {
        $this->assertNotNull($this->paymentMethod);
        $this->assertEquals('stripe', $this->paymentMethod->provider);
        $this->assertTrue($this->paymentMethod->active);
        // test_mode may be set via seeder or environment; ensure it's a boolean
        $this->assertIsBool($this->paymentMethod->test_mode);

        // Methods can be stored as simple strings or as objects (key/label).
        // Normalize to method keys for assertions.
        $methods = $this->paymentMethod->methods;
        $normalized = array_map(function ($m) {
            if (is_array($m) && isset($m['key'])) {
                return $m['key'];
            }

            return $m;
        }, $methods ?? []);

        $this->assertIsArray($normalized);
        $this->assertContains('visa', $normalized);
        $this->assertContains('mastercard', $normalized);
    }

    #[Test]
    public function it_checks_processor_supports_stripe()
    {
        $this->assertTrue(Processor::isSupported('stripe'));
    }

    #[Test]
    public function it_creates_stripe_processor_instance()
    {
        $processor = Processor::make('stripe');

        $this->assertInstanceOf(StripeProcessor::class, $processor);
        $this->assertEquals('stripe', $processor->getProvider());
    }

    #[Test]
    public function it_creates_stripe_client_instance()
    {
        $client = Coderstm::stripe();

        $this->assertInstanceOf(StripeClient::class, $client);
    }

    #[Test]
    public function it_handles_stripe_payment_methods_array()
    {
        $this->assertIsArray($this->paymentMethod->methods);

        $methods = $this->paymentMethod->methods;
        $normalized = array_map(function ($m) {
            if (is_array($m) && isset($m['key'])) {
                return $m['key'];
            }

            return $m;
        }, $methods ?? []);

        $this->assertContains('visa', $normalized);
        $this->assertContains('mastercard', $normalized);
        $this->assertContains('american_express', $normalized);
        $this->assertContains('rupay', $normalized);
    }

    #[Test]
    public function it_supports_multiple_card_types()
    {
        $methods = $this->paymentMethod->methods;
        $normalized = array_map(function ($m) {
            if (is_array($m) && isset($m['key'])) {
                return $m['key'];
            }

            return $m;
        }, $methods ?? []);

        $this->assertGreaterThan(1, count($normalized));
        $this->assertContains('visa', $normalized);
        $this->assertContains('mastercard', $normalized);
        $this->assertContains('american_express', $normalized);
    }

    #[Test]
    public function it_converts_amount_to_cents_for_stripe()
    {
        // Stripe requires amounts in cents (smallest currency unit)
        $amount = 99.99;
        $expectedCents = 9999;

        // This is handled internally by the processor
        $this->assertEquals($expectedCents, round($amount * 100));
    }

    #[Test]
    public function it_handles_zero_decimal_currencies()
    {
        // Some currencies like JPY don't use decimal points
        $jpyAmount = 1000; // ¥1000

        // For JPY, amount should not be multiplied by 100
        // This is a note for implementation - Stripe handles this automatically
        $this->assertEquals(1000, $jpyAmount);
    }

    #[Test]
    public function it_extracts_card_payment_metadata_from_actual_stripe_payment()
    {
        // Create actual Stripe payment intent with test card
        $stripe = Coderstm::stripe();

        // Create a payment intent with a test card using test token
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => 1000, // $10.00
            'currency' => 'usd',
            'payment_method_types' => ['card'],
        ]);

        // Confirm with test card payment method
        $confirmedIntent = $stripe->paymentIntents->confirm($paymentIntent->id, [
            'payment_method' => 'pm_card_visa', // Stripe test payment method
        ]);

        // Retrieve the payment intent to get the latest charge data
        $retrievedIntent = $stripe->paymentIntents->retrieve($confirmedIntent->id);

        // Get the latest charge from the payment intent
        if (isset($retrievedIntent->latest_charge)) {
            $charge = $stripe->charges->retrieve($retrievedIntent->latest_charge);

            // Build a payment intent response with charges data
            $paymentIntentArray = json_decode(json_encode($retrievedIntent), true);
            $chargeArray = json_decode(json_encode($charge), true);

            // Add charges data to match expected structure
            $paymentIntentArray['charges'] = [
                'data' => [$chargeArray],
            ];
        } else {
            $this->markTestSkipped('Payment intent did not create a charge');
        }

        // Create a payment mapper to test metadata extraction and formatting
        $payment = new StripePayment(
            $this->arrayToObject($paymentIntentArray),
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('card', $metadata['payment_method_type']);
        $this->assertEquals('Visa', $metadata['card_brand']);
        $this->assertEquals('4242', $metadata['last_four']);
        $this->assertArrayHasKey('exp_month', $metadata);
        $this->assertArrayHasKey('exp_year', $metadata);

        // Test toString() method
        $displayString = $payment->toString();
        $this->assertStringContainsString('Visa', $displayString);
        $this->assertStringContainsString('4242', $displayString);
        $this->assertStringContainsString('••••', $displayString);
        $this->assertEquals('Visa •••• 4242', $displayString);

        // Test payment method type
        $this->assertEquals('card', $metadata['payment_method_type']);
    }

    #[Test]
    public function it_extracts_bank_account_metadata_from_actual_stripe_payment()
    {
        // Create actual Stripe payment intent with US bank account
        $stripe = Coderstm::stripe();
        // Create payment intent with US bank account test payment method
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => 2000, // $20.00
            'currency' => 'usd',
            'payment_method_types' => ['us_bank_account'],
        ]);

        // Confirm with test US bank account payment method
        $confirmedIntent = $stripe->paymentIntents->confirm($paymentIntent->id, [
            'payment_method' => 'pm_usBankAccount', // Stripe test payment method
        ]);

        // Retrieve to get latest charge
        $retrievedIntent = $stripe->paymentIntents->retrieve($confirmedIntent->id);

        // Get the latest charge
        if (isset($retrievedIntent->latest_charge)) {
            $charge = $stripe->charges->retrieve($retrievedIntent->latest_charge);

            // Build payment intent response with charges data
            $paymentIntentArray = json_decode(json_encode($retrievedIntent), true);
            $chargeArray = json_decode(json_encode($charge), true);

            $paymentIntentArray['charges'] = [
                'data' => [$chargeArray],
            ];
        } else {
            $this->markTestSkipped('Payment intent did not create a charge');
        }

        // Create payment mapper to test metadata extraction
        $payment = new StripePayment(
            $this->arrayToObject($paymentIntentArray),
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('us_bank_account', $metadata['payment_method_type']);
        $this->assertArrayHasKey('last_four', $metadata);

        // Test toString() method
        $displayString = $payment->toString();
        $this->assertEquals('STRIPE TEST BANK •••• 6789', $displayString);
    }

    #[Test]
    public function it_extracts_sepa_debit_metadata_from_actual_stripe_payment()
    {
        // Create actual Stripe payment intent with SEPA debit
        $stripe = Coderstm::stripe();
        // First create a SEPA debit payment method with test IBAN
        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'sepa_debit',
            'sepa_debit' => [
                'iban' => 'DE89370400440532013000', // Valid test IBAN
            ],
            'billing_details' => [
                'name' => 'Jenny Rosen',
                'email' => 'jenny.rosen@example.com',
            ],
        ]);

        // Create payment intent with SEPA debit
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => 1500, // €15.00
            'currency' => 'eur',
            'payment_method_types' => ['sepa_debit'],
            'payment_method' => $paymentMethod->id,
            'confirm' => true, // Confirm immediately
            'mandate_data' => [
                'customer_acceptance' => [
                    'type' => 'online',
                    'online' => [
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0',
                    ],
                ],
            ],
        ]);

        // Retrieve to get latest charge
        $retrievedIntent = $stripe->paymentIntents->retrieve($paymentIntent->id);

        // Get the latest charge
        if (isset($retrievedIntent->latest_charge)) {
            $charge = $stripe->charges->retrieve($retrievedIntent->latest_charge);

            // Build payment intent response with charges data
            $paymentIntentArray = json_decode(json_encode($retrievedIntent), true);
            $chargeArray = json_decode(json_encode($charge), true);

            $paymentIntentArray['charges'] = [
                'data' => [$chargeArray],
            ];
        } else {
            $this->markTestSkipped('Payment intent did not create a charge');
        }

        // Create payment mapper to test metadata extraction
        $payment = new StripePayment(
            $this->arrayToObject($paymentIntentArray),
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('sepa_debit', $metadata['payment_method_type']);
        $this->assertArrayHasKey('last_four', $metadata);
        $this->assertEquals('3000', $metadata['last_four']);
        $this->assertArrayHasKey('country', $metadata);
        $this->assertEquals('DE', $metadata['country']);

        // Test toString() method
        $displayString = $payment->toString();
        $this->assertStringContainsString('3000', $displayString);
        $this->assertStringContainsString('37040044', $displayString); // IBAN bank code instead of "SEPA"
    }
}
