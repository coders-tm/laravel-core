<?php

namespace Tests\Feature\Payment;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Mappers\PayPalPayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\Processor;
use Coderstm\Payment\Processors\PaypalProcessor;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Srmklive\PayPal\Services\PayPal;
use Tests\Feature\FeatureTestCase;

class PaypalProcessorTest extends FeatureTestCase
{
    use WithFaker;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if PayPal credentials are not configured
        if (! env('PAYPAL_CLIENT_ID') || ! env('PAYPAL_CLIENT_SECRET')) {
            $this->markTestSkipped('PayPal credentials not configured. Set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in phpunit.xml');
        }

        // Get PayPal payment method created by seeder (don't filter by enabled status)
        $paymentMethod = PaymentMethod::byProvider(PaymentMethod::PAYPAL);

        if (! $paymentMethod) {
            $this->markTestSkipped('PayPal payment method not found. Run seeders first.');
        }

        // Enable the payment method for testing
        $paymentMethod->update(['active' => true, 'test_mode' => true]);
        PaymentMethod::updateProviderCache(PaymentMethod::PAYPAL);

        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Helper to convert array to object (for PayPal API responses)
     */
    protected function arrayToObject(array $data): object
    {
        return json_decode(json_encode($data));
    }

    #[Test]
    public function it_syncs_paypal_configuration_to_laravel_config()
    {
        // Configuration should be synced after updateProviderCache
        $this->assertEquals($this->paymentMethod->id, config('paypal.id'));
        $this->assertEquals('sandbox', config('paypal.mode'));
        $this->assertNotEmpty(config('paypal.sandbox.client_id'));
        $this->assertNotEmpty(config('paypal.sandbox.client_secret'));
        $this->assertTrue(config('paypal.enabled'));
    }

    #[Test]
    public function it_configures_sandbox_mode_for_test_mode()
    {
        $this->assertEquals('sandbox', config('paypal.mode'));
    }

    #[Test]
    public function it_configures_live_mode_for_production()
    {
        $this->paymentMethod->update(['test_mode' => false]);
        PaymentMethod::updateProviderCache(PaymentMethod::PAYPAL);

        $this->assertEquals('live', config('paypal.mode'));
    }

    #[Test]
    public function it_finds_paypal_payment_method()
    {
        $found = PaymentMethod::findProvider('paypal');

        $this->assertNotNull($found);
        $this->assertEquals($this->paymentMethod->id, $found->id);
        $this->assertEquals('paypal', $found->provider);
    }

    #[Test]
    public function it_checks_if_paypal_provider_exists()
    {
        $this->assertTrue(PaymentMethod::has('paypal'));
    }

    #[Test]
    public function it_retrieves_paypal_via_static_method()
    {
        $paypal = PaymentMethod::paypal();

        $this->assertNotNull($paypal);
        $this->assertEquals($this->paymentMethod->id, $paypal->id);
    }

    #[Test]
    public function it_checks_processor_supports_paypal()
    {
        $this->assertTrue(Processor::isSupported('paypal'));
    }

    #[Test]
    public function it_creates_paypal_processor_instance()
    {
        $processor = Processor::make('paypal');

        $this->assertInstanceOf(PaypalProcessor::class, $processor);
        $this->assertEquals('paypal', $processor->getProvider());
    }

    #[Test]
    public function it_creates_paypal_client_instance()
    {
        // Set required PayPal config defaults
        config([
            'paypal.payment_action' => 'Sale',
            'paypal.currency' => 'USD',
            'paypal.locale' => 'en_US',
            'paypal.validate_ssl' => true,
        ]);

        $client = Coderstm::paypal();

        $this->assertInstanceOf(PayPal::class, $client);
    }

    #[Test]
    public function it_includes_paypal_in_public_payment_methods()
    {
        $publicMethods = PaymentMethod::toPublic();

        $paypalMethod = $publicMethods->firstWhere('provider', 'paypal');

        $this->assertNotNull($paypalMethod);
        $this->assertStringContainsString('paypal', strtolower($paypalMethod['name']));
        $this->assertEquals('paypal', $paypalMethod['provider']);
        $this->assertArrayHasKey('CLIENT_ID', $paypalMethod['credentials']);
        $this->assertNotEmpty($paypalMethod['credentials']['CLIENT_ID']);

        // Secret key should not be published
        $this->assertArrayNotHasKey('CLIENT_SECRET', $paypalMethod['credentials']);
    }

    #[Test]
    public function it_updates_cache_when_paypal_credentials_change()
    {
        // Update credentials
        $newClientId = 'NEW_'.$this->faker->bothify('??##??##??##??##');

        $this->paymentMethod->update([
            'credentials' => collect([
                ['key' => 'CLIENT_ID', 'value' => $newClientId, 'publish' => true],
                ['key' => 'CLIENT_SECRET', 'value' => 'new-secret-key', 'publish' => false],
            ]),
        ]);

        // Reload the model to get fresh data
        $this->paymentMethod->refresh();

        // Cache should be updated automatically via model observer
        $this->assertEquals($newClientId, config('paypal.sandbox.client_id'));
    }

    #[Test]
    public function it_validates_client_id_format()
    {
        $clientId = $this->paymentMethod->getConfigs()['CLIENT_ID'];

        // Client ID should be a long alphanumeric string
        $this->assertNotEmpty($clientId);
        $this->assertIsString($clientId);
        $this->assertGreaterThan(50, strlen($clientId));
    }

    #[Test]
    public function it_validates_client_secret_format()
    {
        $clientSecret = $this->paymentMethod->getConfigs()['CLIENT_SECRET'];

        // Client secret should be a long alphanumeric string
        $this->assertNotEmpty($clientSecret);
        $this->assertIsString($clientSecret);
        $this->assertGreaterThan(50, strlen($clientSecret));
    }

    #[Test]
    public function it_extracts_paypal_wallet_metadata_from_capture()
    {
        $capture = $this->arrayToObject([
            'id' => 'CAPTURE123',
            'status' => 'COMPLETED',
            'amount' => ['currency_code' => 'USD', 'value' => '100.00'],
            'seller_receivable_breakdown' => [
                'paypal_fee' => ['currency_code' => 'USD', 'value' => '3.20'],
            ],
            'payer' => [
                'email_address' => 'buyer@example.com',
                'payer_id' => 'PAYERID123',
                'name' => ['given_name' => 'John', 'surname' => 'Doe'],
            ],
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 100.00,
        ]);
        $payment = new PayPalPayment(
            $capture,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('wallet', $metadata['payment_method_type']);
        $this->assertEquals('paypal', $metadata['wallet_type']);
        $this->assertEquals('buyer@example.com', $metadata['payer_email']);

        $this->assertEquals('PayPal (buyer@example.com)', $payment->toString());
    }

    #[Test]
    public function it_extracts_card_via_paypal_metadata_from_capture()
    {
        $capture = $this->arrayToObject([
            'id' => 'CAPTURE456',
            'status' => 'COMPLETED',
            'amount' => ['currency_code' => 'USD', 'value' => '50.00'],
            'payment_source' => [
                'card' => [
                    'brand' => 'visa',
                    'last_digits' => '1234',
                    'type' => 'CREDIT',
                ],
            ],
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 50.00,
        ]);
        $payment = new PayPalPayment(
            $capture,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('card', $metadata['payment_method_type']);
        $this->assertEquals('Visa', $metadata['card_brand']);
        $this->assertEquals('1234', $metadata['last_four']);

        $this->assertEquals('Visa •••• 1234 (via PayPal)', $payment->toString());
    }

    #[Test]
    public function it_extracts_venmo_metadata_from_capture()
    {
        $capture = $this->arrayToObject([
            'id' => 'CAPTURE789',
            'status' => 'COMPLETED',
            'amount' => ['currency_code' => 'USD', 'value' => '25.00'],
            'payment_source' => [
                'venmo' => [
                    'user_name' => 'johndoe',
                    'email_address' => 'john@example.com',
                ],
            ],
        ]);

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 25.00,
        ]);
        $payment = new PayPalPayment(
            $capture,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('wallet', $metadata['payment_method_type']);
        $this->assertEquals('venmo', $metadata['wallet_type']);
        $this->assertEquals('johndoe', $metadata['venmo_username']);

        $this->assertEquals('Venmo (@johndoe)', $payment->toString());
    }
}
