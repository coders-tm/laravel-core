<?php

namespace Tests\Feature\Payment;

use Coderstm\Exceptions\PaymentException;
use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\ExchangeRate;
use Coderstm\Payment\Mappers\PayuPayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\Processor;
use Coderstm\Payment\Processors\PayuProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PayuProcessorTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Create INR exchange rate
        ExchangeRate::create(['currency' => 'INR', 'rate' => 1.0]);

        // Create PayU payment method
        $this->paymentMethod = PaymentMethod::create([
            'name' => 'PayU',
            'provider' => 'payu',
            'active' => true,
        ]);

        $this->paymentMethod->credentials = collect([
            ['key' => 'MERCHANT_KEY', 'value' => 'test_key_123', 'publish' => true],
            ['key' => 'MERCHANT_SALT', 'value' => 'test_salt_123', 'publish' => false],
        ]);
        $this->paymentMethod->save();

        PaymentMethod::updateProviderCache(PaymentMethod::PAYU);

        config([
            'payu.merchant_key' => 'test_key_123',
            'payu.merchant_salt' => 'test_salt_123',
            'payu.test_mode' => true,
        ]);
    }

    protected function createMockPayable()
    {
        return Payable::make([
            'reference_id' => '12345',
            'grand_total' => 100.00,
            'currency' => 'INR',
            'billing_address' => [
                'country_code' => 'IN',
                'country' => 'India',
            ],
            'description' => 'Test Payment Description',
            'source' => (object) ['id' => 1],
        ]);
    }

    #[Test]
    public function it_creates_payu_processor_instance()
    {
        $processor = Processor::make('payu');

        $this->assertInstanceOf(PayuProcessor::class, $processor);
        $this->assertEquals('payu', $processor->getProvider());
    }

    #[Test]
    public function it_sets_up_payu_payment_intent_and_calculates_hash()
    {
        $processor = new PayuProcessor;
        $processor->setPaymentMethod($this->paymentMethod);
        $payable = $this->createMockPayable();

        $result = $processor->setupPaymentIntent(new Request, $payable);

        $this->assertEquals('test_key_123', $result['key']);
        $this->assertNotEmpty($result['txnid']);
        $this->assertEquals('100.00', $result['amount']);
        $this->assertEquals('Payment for checkout', $result['productinfo']);
        $this->assertEquals('guest@example.com', $result['email']);
        $this->assertNotEmpty($result['hash']);
        $this->assertEquals('https://test.payu.in/_payment', $result['checkout_url']);
        $this->assertArrayHasKey('state_id', $result);

        // Calculate expected SHA512 request hash manually
        // key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10|salt
        $expectedHashSeq = sprintf(
            'test_key_123|%s|100.00|Payment for checkout|Guest|guest@example.com|%s||||||||||test_salt_123',
            $result['txnid'],
            $result['state_id']
        );
        $expectedHash = hash('sha512', $expectedHashSeq);

        $this->assertEquals($expectedHash, $result['hash']);

        // Verify payment record is created in pending status
        $this->assertDatabaseHas('payments', [
            'uuid' => $result['state_id'],
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_confirms_payu_payment_with_valid_hash()
    {
        $processor = Processor::make('payu');
        $processor->setPaymentMethod($this->paymentMethod);

        $payable = $this->createMockPayable();

        // Simulate success response POST parameters
        $responseData = [
            'mihpayid' => 'payu_mih_123',
            'mode' => 'CC',
            'status' => 'success',
            'key' => 'test_key_123',
            'txnid' => 'TXN123',
            'amount' => '100.00',
            'productinfo' => 'Payment for checkout',
            'firstname' => 'Guest',
            'email' => 'guest@example.com',
            'udf1' => 'state_uuid_123',
        ];

        // Generate matching SHA512 response hash
        // salt|status|udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
        $hashSeq = 'test_salt_123|success||||||||||state_uuid_123|guest@example.com|Guest|Payment for checkout|100.00|TXN123|test_key_123';
        $responseData['hash'] = hash('sha512', $hashSeq);

        $request = new Request($responseData);
        $result = $processor->confirmPayment($request, $payable);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('payu_mih_123', $result->getTransactionId());
        $this->assertInstanceOf(PayuPayment::class, $result->getPaymentData());
        $this->assertEquals('Credit Card (•••• 1234)', $result->getPaymentData()->toString());
    }

    #[Test]
    public function it_fails_confirmation_with_invalid_hash()
    {
        $processor = Processor::make('payu');
        $processor->setPaymentMethod($this->paymentMethod);

        $payable = $this->createMockPayable();

        $responseData = [
            'mihpayid' => 'payu_mih_123',
            'mode' => 'CC',
            'status' => 'success',
            'key' => 'test_key_123',
            'txnid' => 'TXN123',
            'amount' => '100.00',
            'productinfo' => 'Payment for checkout',
            'firstname' => 'Guest',
            'email' => 'guest@example.com',
            'udf1' => 'state_uuid_123',
            'hash' => 'invalid_signature_hash',
        ];

        $request = new Request($responseData);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Invalid PayU transaction signature/hash.');

        $processor->confirmPayment($request, $payable);
    }

    #[Test]
    public function it_handles_payu_success_callback()
    {
        $processor = new PayuProcessor;
        $processor->setPaymentMethod($this->paymentMethod);

        $payment = Payment::create([
            'paymentable_type' => 'App\Models\Order',
            'paymentable_id' => 1,
            'payment_method_id' => $this->paymentMethod->id,
            'transaction_id' => 'pending_123',
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $responseData = [
            'mihpayid' => 'payu_mih_123',
            'mode' => 'NB',
            'bankcode' => 'HDFC',
            'status' => 'success',
            'key' => 'test_key_123',
            'txnid' => 'TXN123',
            'amount' => '100.00',
            'productinfo' => 'Payment for checkout',
            'firstname' => 'Guest',
            'email' => 'guest@example.com',
            'udf1' => $payment->uuid,
            'state' => $payment->uuid,
        ];

        $hashSeq = 'test_salt_123|success||||||||||'.$payment->uuid.'|guest@example.com|Guest|Payment for checkout|100.00|TXN123|test_key_123';
        $responseData['hash'] = hash('sha512', $hashSeq);

        $request = new Request($responseData);
        $result = $processor->handleSuccessCallback($request);

        $this->assertEquals('success', $result->getMessageType());
        $this->assertEquals('PayU payment was successful.', $result->getMessage());

        // Verify database updated
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'transaction_id' => 'payu_mih_123',
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function it_handles_payu_cancel_callback()
    {
        $processor = new PayuProcessor;
        $processor->setPaymentMethod($this->paymentMethod);

        $payment = Payment::create([
            'paymentable_type' => 'App\Models\Order',
            'paymentable_id' => 1,
            'payment_method_id' => $this->paymentMethod->id,
            'transaction_id' => 'pending_123',
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $request = new Request([
            'state' => $payment->uuid,
            'unmappedstatus' => 'userCancelled',
        ]);

        $result = $processor->handleCancelCallback($request);

        $this->assertEquals('success', $result->getMessageType());
        $this->assertEquals('PayU payment was cancelled.', $result->getMessage());

        // Verify status marked as failed
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_converts_unsupported_currency_to_inr_using_internet_rate_when_available()
    {
        $exchangeRatesMock = \Mockery::mock(\AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate::class);
        $exchangeRatesMock->shouldReceive('exchangeRate')
            ->with('USD', 'INR')
            ->andReturn(85.50);
        $this->app->instance(\AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate::class, $exchangeRatesMock);

        $processor = new PayuProcessor;
        $processor->setPaymentMethod($this->paymentMethod);

        // Create exchange rate for KES so that it's recognized as a valid currency by the Payable model
        ExchangeRate::create(['currency' => 'KES', 'rate' => 0.01]);

        // Create a Payable with KES currency (via country code KE)
        $payable = Payable::make([
            'reference_id' => '99999',
            'grand_total' => 10.00,
            'billing_address' => [
                'country_code' => 'KE',
                'country' => 'Kenya',
            ],
            'description' => 'KES Conversion Test',
            'source' => (object) ['id' => 1],
        ]);

        // Ensure no exchange rate for INR exists in database to trigger fallback logic
        ExchangeRate::where('currency', 'INR')->delete();

        $result = $processor->setupPaymentIntent(new Request, $payable);

        // Expected amount is 10.00 * 85.50 = 855.00
        $this->assertEquals('855.00', $result['amount']);
    }

    #[Test]
    public function it_converts_unsupported_currency_to_inr_using_fallback_rate_when_internet_fails()
    {
        $exchangeRatesMock = \Mockery::mock(\AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate::class);
        $exchangeRatesMock->shouldReceive('exchangeRate')
            ->with('USD', 'INR')
            ->andThrow(new \RuntimeException('API failure'));
        $this->app->instance(\AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate::class, $exchangeRatesMock);

        $processor = new PayuProcessor;
        $processor->setPaymentMethod($this->paymentMethod);

        // Create exchange rate for KES so that it's recognized as a valid currency by the Payable model
        ExchangeRate::create(['currency' => 'KES', 'rate' => 0.01]);

        // Create a Payable with KES currency (via country code KE)
        $payable = Payable::make([
            'reference_id' => '99999',
            'grand_total' => 10.00,
            'billing_address' => [
                'country_code' => 'KE',
                'country' => 'Kenya',
            ],
            'description' => 'KES Conversion Test',
            'source' => (object) ['id' => 1],
        ]);

        // Ensure no exchange rate for INR exists in database to trigger fallback logic
        ExchangeRate::where('currency', 'INR')->delete();

        $result = $processor->setupPaymentIntent(new Request, $payable);

        // Expected amount is 10.00 (grand total fallback)
        $this->assertEquals('10.00', $result['amount']);
    }
}
