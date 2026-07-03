<?php

namespace Tests\Feature\Payment;

use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Mappers\PaystackPayment;
use Coderstm\Payment\Payable;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PaystackProcessorTest extends FeatureTestCase
{
    use WithFaker;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if Paystack credentials are not configured
        if (! env('PAYSTACK_PUBLIC_KEY') || ! env('PAYSTACK_SECRET_KEY')) {
            $this->markTestSkipped('Paystack credentials not configured. Set PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY in phpunit.xml');
        }

        // Get Paystack payment method WITHOUT enabled filter (direct query)
        $paymentMethod = PaymentMethod::byProvider(PaymentMethod::PAYSTACK);

        if (! $paymentMethod) {
            $this->markTestSkipped('Paystack payment method not found. Run seeders first.');
        }

        // Enable programmatically for testing
        $paymentMethod->update(['active' => true, 'test_mode' => true]);
        PaymentMethod::updateProviderCache(PaymentMethod::PAYSTACK);

        $this->paymentMethod = $paymentMethod;
    }

    #[Test]
    public function it_extracts_card_payment_metadata_from_transaction()
    {
        $transaction = [
            'id' => 123456,
            'reference' => 'PST-TEST-123',
            'status' => 'success',
            'channel' => 'card',
            'authorization' => [
                'authorization_code' => 'AUTH_pmx3mgawyd',
                'card_type' => 'visa',
                'last4' => '4081',
                'exp_month' => '12',
                'exp_year' => '2030',
                'bin' => '408408',
                'bank' => 'TEST BANK',
                'country_code' => 'NG',
            ],
            'amount' => 1000000, // 10,000 NGN in kobo
            'currency' => 'NGN',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 10000.00,
        ]);
        $payment = new PaystackPayment(
            $transaction,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('card', $metadata['payment_method_type']);
        $this->assertEquals('4081', $metadata['last_four']);
        $this->assertEquals('Visa', $metadata['card_brand']);
        $this->assertEquals('TEST BANK', $metadata['bank_name']);

        $this->assertEquals('Visa •••• 4081 (TEST BANK)', $payment->toString());
    }

    #[Test]
    public function it_extracts_mobile_money_metadata_from_transaction()
    {
        $transaction = [
            'id' => 123457,
            'reference' => 'PST-TEST-456',
            'status' => 'success',
            'channel' => 'mobile_money',
            'authorization' => [
                'mobile_money_number' => '+233123456789',
            ],
            'amount' => 50000, // 500 GHS in pesewas
            'currency' => 'GHS',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 500.00,
        ]);
        $payment = new PaystackPayment(
            $transaction,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('mobile_money', $metadata['payment_method_type']);
        $this->assertEquals('+233123456789', $metadata['mobile_number']);

        $this->assertEquals('Mobile Money (+233123456789)', $payment->toString());
    }

    #[Test]
    public function it_extracts_ussd_payment_metadata_from_transaction()
    {
        $transaction = [
            'id' => 123458,
            'reference' => 'PST-TEST-789',
            'status' => 'success',
            'channel' => 'ussd',
            'authorization' => [
                'bank' => 'GTBank',
            ],
            'amount' => 200000, // 2,000 NGN in kobo
            'currency' => 'NGN',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 2000.00,
        ]);
        $payment = new PaystackPayment(
            $transaction,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('ussd', $metadata['payment_method_type']);
        $this->assertEquals('GTBank', $metadata['bank_name']);

        $this->assertEquals('USSD (GTBank)', $payment->toString());
    }

    #[Test]
    public function it_extracts_bank_transfer_metadata_from_transaction()
    {
        $transaction = [
            'id' => 123459,
            'reference' => 'PST-TEST-012',
            'status' => 'success',
            'channel' => 'bank_transfer',
            'metadata' => [
                'account_number' => '0123456789',
            ],
            'amount' => 1500000, // 15,000 NGN in kobo
            'currency' => 'NGN',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 15000.00,
        ]);
        $payment = new PaystackPayment(
            $transaction,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('bank_transfer', $metadata['payment_method_type']);

        $this->assertEquals('Bank Transfer', $payment->toString());
    }

    #[Test]
    public function it_extracts_qr_payment_metadata_from_transaction()
    {
        $transaction = [
            'id' => 123460,
            'reference' => 'PST-TEST-345',
            'status' => 'success',
            'channel' => 'qr',
            'amount' => 100000, // 1,000 NGN in kobo
            'currency' => 'NGN',
        ];

        // Use mapper to extract metadata
        $payable = Payable::make([
            'grand_total' => 1000.00,
        ]);
        $payment = new PaystackPayment(
            $transaction,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('qr', $metadata['payment_method_type']);

        $this->assertEquals('QR Code', $payment->toString());
    }
}
