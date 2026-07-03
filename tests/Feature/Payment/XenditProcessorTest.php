<?php

namespace Tests\Feature\Payment;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\ExchangeRate;
use Coderstm\Payment\Mappers\XenditPayment;
use Coderstm\Payment\Payable;
use Coderstm\Services\Payment\XenditClient;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class XenditProcessorTest extends FeatureTestCase
{
    use WithFaker;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests if Xendit credentials are not configured
        if (! env('XENDIT_PUBLIC_KEY') || ! env('XENDIT_SECRET_KEY')) {
            $this->markTestSkipped('Xendit credentials not configured. Set XENDIT_PUBLIC_KEY and XENDIT_SECRET_KEY in phpunit.xml');
        }

        // Get Xendit payment method created by seeder (don't filter by enabled status)
        $paymentMethod = PaymentMethod::byProvider(PaymentMethod::XENDIT);

        if (! $paymentMethod) {
            $this->markTestSkipped('Xendit payment method not found. Run seeders first.');
        }

        // Enable the payment method for testing with real keys from environment
        $paymentMethod->update([
            'active' => true,
            'test_mode' => true,
            'credentials' => collect([
                ['key' => 'PUBLIC_KEY', 'value' => env('XENDIT_PUBLIC_KEY'), 'publish' => true],
                ['key' => 'SECRET_KEY', 'value' => env('XENDIT_SECRET_KEY'), 'publish' => false],
            ]),
        ]);
        PaymentMethod::updateProviderCache(PaymentMethod::XENDIT);

        $this->paymentMethod = $paymentMethod;

        // Add exchange rate for IDR conversion testing
        ExchangeRate::updateOrCreate(['currency' => 'IDR'], ['rate' => 15000.00]);
    }

    /**
     * Helper method to create a test invoice via Xendit API
     */
    protected function createTestInvoice(string $prefix, float $amount = 100000.00): array
    {
        $xendit = Coderstm::xendit();

        $invoiceData = [
            'external_id' => $prefix.'_'.uniqid(),
            'amount' => $amount,
            'currency' => 'IDR',
            'payer_email' => 'test@xendit.co',
            'description' => "Test {$prefix} Payment for Metadata Extraction",
        ];

        $invoice = $xendit->createInvoice($invoiceData);

        // Convert Xendit Invoice object to array if needed
        return is_array($invoice) ? $invoice : json_decode(json_encode($invoice), true);
    }

    /**
     * Simulate a paid invoice response (as Xendit would return after payment)
     * This mimics what getInvoice() returns when an invoice is paid
     */
    protected function simulatePaidInvoice(array $invoice, array $paymentDetails): array
    {
        // Simulate documented InvoiceCallback + paid invoice fields per InvoiceApi.md
        return array_merge($invoice, [
            'status' => 'PAID',
            // Core payment settlement fields
            'paid_amount' => $invoice['amount'], // Xendit returns total paid amount separately
            'paid_at' => now()->toIso8601String(),
            'payment_id' => $paymentDetails['payment_id'] ?? 'xnd_'.uniqid(),
            'payment_method' => $paymentDetails['payment_method'] ?? null, // High-level: CREDIT_CARD, EWALLET, BANK_TRANSFER, RETAIL_OUTLET, QR_CODE
            'payment_channel' => $paymentDetails['payment_channel'] ?? null, // Specific channel: BNI, OVO, ALFAMART, QRIS, etc.
            'payment_destination' => $paymentDetails['payment_destination'] ?? ($paymentDetails['account_number'] ?? ($paymentDetails['payment_code'] ?? null)), // For VA / retail outlets
            // Informational fields commonly present in callback sample
            'merchant_name' => $paymentDetails['merchant_name'] ?? 'Xendit',
            'payer_email' => $paymentDetails['payer_email'] ?? ($invoice['payer_email'] ?? 'test@xendit.co'),
            // Merge remaining payment-specific detail fields (card brand, bank_code, etc.)
        ] + $paymentDetails);
    }

    #[Test]
    public function it_creates_xendit_client_instance()
    {

        $client = Coderstm::xendit();

        $this->assertInstanceOf(XenditClient::class, $client);
    }

    #[Test]
    public function it_extracts_card_payment_metadata_from_paid_invoice()
    {
        // Step 1: Create invoice via Xendit API (proves connectivity)
        $invoice = $this->createTestInvoice('test_card', 100000.00);

        // Step 2: Simulate payment completion (as would happen in production)
        // In production, user pays on Xendit's page, then we fetch the paid invoice
        $paidInvoice = $this->simulatePaidInvoice($invoice, [
            'payment_method' => 'CREDIT_CARD',
            'payment_channel' => 'CREDIT_CARD',
            'credit_card_charge_id' => 'cc_charge_'.uniqid(),
            'card_type' => 'CREDIT',
            'card_brand' => 'visa',
            'masked_card_number' => '400000XXXXXX1000',
            'payment_destination' => '400000XXXXXX1000',
        ]);

        // Step 3: Extract metadata from paid invoice (as XenditProcessor does)
        $payable = Payable::make([
            'grand_total' => 1000.00,
        ]);
        $payment = new XenditPayment(
            $paidInvoice,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('card', $metadata['payment_method_type']);
        $this->assertEquals('VISA', $metadata['card_brand']);
        $this->assertEquals('1000', $metadata['last_four']);

        // Assert documented paid invoice fields
        $this->assertEquals($invoice['amount'], $paidInvoice['paid_amount']);
        $this->assertEquals('CREDIT_CARD', $paidInvoice['payment_method']);
        $this->assertEquals('CREDIT_CARD', $paidInvoice['payment_channel']);
        $this->assertNotEmpty($paidInvoice['payment_id']);
        $this->assertEquals('400000XXXXXX1000', $paidInvoice['payment_destination']);

        // Test formatter
        $this->assertEquals('VISA •••• 1000', $payment->toString());
    }

    #[Test]
    public function it_extracts_ewallet_metadata_from_paid_invoice()
    {
        // Step 1: Create invoice via Xendit API
        $invoice = $this->createTestInvoice('test_ovo', 50000.00);

        // Step 2: Simulate OVO payment completion
        $paidInvoice = $this->simulatePaidInvoice($invoice, [
            'payment_method' => 'EWALLET',
            'payment_channel' => 'OVO',
            'channel_code' => 'ID_OVO',
            'ewallet_type' => 'OVO',
            'payment_destination' => 'OVO',
        ]);

        // Step 3: Extract metadata from paid invoice
        $payable = Payable::make([
            'grand_total' => 500.00,
        ]);
        $payment = new XenditPayment(
            $paidInvoice,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('wallet', $metadata['payment_method_type']);
        $this->assertEquals('OVO', $metadata['wallet_type']);

        // Assert documented fields
        $this->assertEquals($invoice['amount'], $paidInvoice['paid_amount']);
        $this->assertEquals('EWALLET', $paidInvoice['payment_method']);
        $this->assertEquals('OVO', $paidInvoice['payment_channel']);
        $this->assertEquals('OVO', $paidInvoice['payment_destination']);

        // Test formatter
        $this->assertEquals('OVO', $payment->toString());
    }

    #[Test]
    public function it_extracts_virtual_account_metadata_from_paid_invoice()
    {
        // Step 1: Create invoice via Xendit API
        $invoice = $this->createTestInvoice('test_va', 100000.00);

        // Step 2: Simulate BNI virtual account payment completion
        $paidInvoice = $this->simulatePaidInvoice($invoice, [
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BNI',
            'bank_code' => 'BNI',
            'account_number' => '8808081212345678',
        ]);

        // Step 3: Extract metadata from paid invoice
        $payable = Payable::make([
            'grand_total' => 1000.00,
        ]);
        $payment = new XenditPayment(
            $paidInvoice,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('virtual_account', $metadata['payment_method_type']);
        $this->assertEquals('BNI', $metadata['bank_name']);

        // Assert documented fields
        $this->assertEquals($invoice['amount'], $paidInvoice['paid_amount']);
        $this->assertEquals('BANK_TRANSFER', $paidInvoice['payment_method']);
        $this->assertEquals('BNI', $paidInvoice['payment_channel']);
        $this->assertEquals('8808081212345678', $paidInvoice['payment_destination']);

        // Test formatter
        $this->assertEquals('Virtual Account (BNI)', $payment->toString());
    }

    #[Test]
    public function it_extracts_retail_outlet_metadata_from_paid_invoice()
    {
        // Step 1: Create invoice via Xendit API
        $invoice = $this->createTestInvoice('test_retail', 75000.00);

        // Step 2: Simulate ALFAMART payment completion
        $paidInvoice = $this->simulatePaidInvoice($invoice, [
            'payment_method' => 'RETAIL_OUTLET',
            'payment_channel' => 'ALFAMART',
            'retail_outlet_name' => 'ALFAMART',
            'payment_code' => 'TEST'.rand(100000, 999999),
        ]);

        // Step 3: Extract metadata from paid invoice
        $payable = Payable::make([
            'grand_total' => 750.00,
        ]);
        $payment = new XenditPayment(
            $paidInvoice,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('retail_outlet', $metadata['payment_method_type']);
        $this->assertEquals('ALFAMART', $metadata['retail_outlet']);

        // Assert documented fields
        $this->assertEquals($invoice['amount'], $paidInvoice['paid_amount']);
        $this->assertEquals('RETAIL_OUTLET', $paidInvoice['payment_method']);
        $this->assertEquals('ALFAMART', $paidInvoice['payment_channel']);
        $this->assertNotEmpty($paidInvoice['payment_destination']);

        // Test formatter
        $this->assertEquals('ALFAMART', $payment->toString());
    }

    #[Test]
    public function it_extracts_qr_code_metadata_from_paid_invoice()
    {
        // Step 1: Create invoice via Xendit API
        $invoice = $this->createTestInvoice('test_qr', 25000.00);

        // Step 2: Simulate QRIS payment completion
        $paidInvoice = $this->simulatePaidInvoice($invoice, [
            'payment_method' => 'QR_CODE',
            'payment_channel' => 'QRIS',
            'qr_string' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA...',
        ]);

        // Step 3: Extract metadata from paid invoice
        $payable = Payable::make([
            'grand_total' => 250.00,
        ]);
        $payment = new XenditPayment(
            $paidInvoice,
            $this->paymentMethod
        );

        $metadata = $payment->getMetadata();
        $this->assertEquals('qr_code', $metadata['payment_method_type']);

        // Assert documented fields
        $this->assertEquals($invoice['amount'], $paidInvoice['paid_amount']);
        $this->assertEquals('QR_CODE', $paidInvoice['payment_method']);
        $this->assertEquals('QRIS', $paidInvoice['payment_channel']);

        // Test formatter
        $this->assertEquals('QR Code', $payment->toString());
    }
}
