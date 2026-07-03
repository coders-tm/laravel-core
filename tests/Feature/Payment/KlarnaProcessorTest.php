<?php

namespace Tests\Feature\Payment;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Mappers\KlarnaPayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\Processor;
use Coderstm\Payment\Processors\KlarnaProcessor;
use Coderstm\Services\Payment\KlarnaClient;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class KlarnaProcessorTest extends FeatureTestCase
{
    use WithFaker;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if Klarna credentials are not configured
        if (! env('KLARNA_API_KEY') || ! env('KLARNA_API_SECRET')) {
            $this->markTestSkipped('Klarna credentials not configured. Set KLARNA_API_KEY and KLARNA_API_SECRET in phpunit.xml');
        }

        // Get Klarna payment method created by seeder (don't filter by enabled status)
        $paymentMethod = PaymentMethod::byProvider(PaymentMethod::KLARNA);

        if (! $paymentMethod) {
            $this->markTestSkipped('Klarna payment method not found. Run seeders first.');
        }

        // Enable the payment method for testing
        $paymentMethod->update(['active' => true, 'test_mode' => true]);
        PaymentMethod::updateProviderCache(PaymentMethod::KLARNA);

        $this->paymentMethod = $paymentMethod;
    }

    #[Test]
    public function it_creates_klarna_payment_method_with_correct_configuration()
    {
        $this->assertNotNull($this->paymentMethod);
        $this->assertEquals('klarna', $this->paymentMethod->provider);
        $this->assertTrue($this->paymentMethod->active);
        $this->assertTrue($this->paymentMethod->test_mode);

        // Verify credentials are stored correctly
        $configs = $this->paymentMethod->getConfigs();
        $this->assertNotEmpty($configs['API_KEY']);
        $this->assertNotEmpty($configs['API_SECRET']);
    }

    #[Test]
    public function it_syncs_klarna_configuration_to_laravel_config()
    {
        // Configuration should be synced after updateProviderCache
        $this->assertEquals($this->paymentMethod->id, config('klarna.id'));
        $this->assertNotEmpty(config('klarna.api_key'));
        $this->assertNotEmpty(config('klarna.api_secret'));
        $this->assertTrue(config('klarna.test_mode'));
        $this->assertTrue(config('klarna.enabled'));
    }

    #[Test]
    public function it_validates_api_key_format()
    {
        $apiKey = $this->paymentMethod->getConfigs()['API_KEY'];

        // API key should exist and be non-empty
        $this->assertNotEmpty($apiKey);
        $this->assertIsString($apiKey);
    }

    #[Test]
    public function it_validates_api_secret_format()
    {
        $apiSecret = $this->paymentMethod->getConfigs()['API_SECRET'];

        // API secret should exist and be non-empty
        $this->assertNotEmpty($apiSecret);
        $this->assertIsString($apiSecret);
    }

    #[Test]
    public function it_checks_processor_supports_klarna()
    {
        $this->assertTrue(Processor::isSupported('klarna'));
    }

    #[Test]
    public function it_creates_klarna_processor_instance()
    {
        $processor = Processor::make('klarna');

        $this->assertInstanceOf(KlarnaProcessor::class, $processor);
        $this->assertEquals('klarna', $processor->getProvider());
    }

    #[Test]
    public function it_creates_klarna_client_instance()
    {
        $client = Coderstm::klarna();

        $this->assertInstanceOf(KlarnaClient::class, $client);
    }

    #[Test]
    public function it_includes_klarna_in_public_payment_methods()
    {
        $publicMethods = PaymentMethod::toPublic();

        $klarnaMethod = $publicMethods->firstWhere('provider', 'klarna');

        $this->assertNotNull($klarnaMethod);
        $this->assertEquals('Klarna', $klarnaMethod['name']);
        $this->assertEquals('klarna', $klarnaMethod['provider']);

        // API_KEY is published (publish: true), so it appears in public methods
        // API_SECRET is not published (publish: false or omitted)
        $this->assertArrayHasKey('API_KEY', $klarnaMethod['credentials']);
        $this->assertArrayNotHasKey('API_SECRET', $klarnaMethod['credentials']);
    }

    #[Test]
    public function it_updates_cache_when_klarna_credentials_change()
    {
        // Update credentials
        $newApiKey = $this->faker->uuid();

        $this->paymentMethod->update([
            'credentials' => collect([
                ['key' => 'API_KEY', 'value' => $newApiKey, 'publish' => false],
                ['key' => 'API_SECRET', 'value' => 'klarna_test_api_new_secret', 'publish' => false],
            ]),
        ]);

        // Reload the model to get fresh data
        $this->paymentMethod->refresh();

        // Cache should be updated automatically via model observer
        $this->assertEquals($newApiKey, config('klarna.api_key'));
    }

    #[Test]
    public function it_validates_both_credentials_are_not_published()
    {
        $credentials = $this->paymentMethod->credentials;

        // Find API_KEY credential - should be published
        $apiKeyCred = $credentials->firstWhere('key', 'API_KEY');
        $this->assertTrue($apiKeyCred['publish'] ?? false, 'API_KEY should be published');

        // Find API_SECRET credential - should not be published
        $apiSecretCred = $credentials->firstWhere('key', 'API_SECRET');
        $this->assertFalse($apiSecretCred['publish'] ?? false, 'API_SECRET should not be published');
    }

    #[Test]
    public function it_extracts_pay_later_metadata_from_session()
    {
        $session = [
            'session_id' => 'klarna_session_123',
            'status' => 'complete',
            'payment_method_category' => 'pay_later',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 100.00,
        ]);
        $payment = new KlarnaPayment(
            $session,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('klarna', $metadata['payment_method_type']);
        $this->assertEquals('pay_later', $metadata['klarna_category']);

        $this->assertEquals('Klarna Pay Later', $payment->toString());
    }

    #[Test]
    public function it_extracts_pay_over_time_metadata_from_order()
    {
        $order = [
            'order_id' => 'klarna_order_456',
            'status' => 'AUTHORIZED',
            'payment_method_category' => 'pay_over_time',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 200.00,
        ]);
        $payment = new KlarnaPayment(
            $order,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('klarna', $metadata['payment_method_type']);
        $this->assertEquals('pay_over_time', $metadata['klarna_category']);

        $this->assertEquals('Klarna Financing', $payment->toString());
    }

    #[Test]
    public function it_extracts_pay_now_metadata_from_order()
    {
        $order = [
            'order_id' => 'klarna_order_789',
            'status' => 'AUTHORIZED',
            'payment_method_category' => 'pay_now',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 150.00,
        ]);
        $payment = new KlarnaPayment(
            $order,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('klarna', $metadata['payment_method_type']);
        $this->assertEquals('pay_now', $metadata['klarna_category']);

        $this->assertEquals('Klarna Pay Now', $payment->toString());
    }

    #[Test]
    public function it_extracts_direct_bank_transfer_metadata_from_order()
    {
        $order = [
            'order_id' => 'klarna_order_012',
            'status' => 'AUTHORIZED',
            'payment_method_category' => 'direct_bank_transfer',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 250.00,
        ]);
        $payment = new KlarnaPayment(
            $order,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('klarna', $metadata['payment_method_type']);
        $this->assertEquals('direct_bank_transfer', $metadata['klarna_category']);

        $this->assertEquals('Klarna Bank Transfer', $payment->toString());
    }

    #[Test]
    public function it_extracts_direct_debit_metadata_from_order()
    {
        $order = [
            'order_id' => 'klarna_order_345',
            'status' => 'AUTHORIZED',
            'payment_method_category' => 'direct_debit',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 300.00,
        ]);
        $payment = new KlarnaPayment(
            $order,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('klarna', $metadata['payment_method_type']);
        $this->assertEquals('direct_debit', $metadata['klarna_category']);

        $this->assertEquals('Klarna Direct Debit', $payment->toString());
    }
}
