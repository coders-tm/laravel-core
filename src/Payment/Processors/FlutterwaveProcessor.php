<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\CallbackResult;
use Coderstm\Payment\Mappers\FlutterwavePayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlutterwaveProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    private const SUPPORTED_CURRENCIES = ['GBP', 'CAD', 'XAF', 'CLP', 'COP', 'EGP', 'EUR', 'GHS', 'GNF', 'KES', 'MWK', 'MAD', 'NGN', 'RWF', 'SLL', 'STD', 'ZAR', 'TZS', 'UGX', 'USD', 'XOF', 'ZMW'];

    public function getProvider(): string
    {
        return PaymentMethod::FLUTTERWAVE;
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function handleSuccessCallback(Request $request): CallbackResult
    {
        try {
            $stateId = $request->query('state');

            if (! $stateId) {
                Log::error('Flutterwave success callback: Missing state parameter');

                return CallbackResult::failed('Invalid payment session.');
            }

            $payment = Payment::where('uuid', $stateId)->first();

            if (! $payment) {
                Log::error('Flutterwave success callback: Payment not found', ['state_id' => $stateId]);

                return CallbackResult::failed('Payment session expired or not found.');
            }

            // Verify with Flutterwave
            $txRef = $request->input('tx_ref');
            $transactionId = $request->input('transaction_id');

            // Allow matching by our tx_ref (which is stored in transaction_id for pending) or their ID
            if (! $transactionId && ! $txRef) {
                return CallbackResult::failed('Missing transaction reference.');
            }

            $flutterwave = Coderstm::flutterwave();
            $response = $flutterwave->verifyTransaction($transactionId ?? $txRef);

            if (! $response || ($response['status'] ?? '') !== 'success') {
                return CallbackResult::failed('Payment verification failed at gateway.');
            }

            $data = $response['data'];

            // Validation
            if (isset($payment->metadata['gateway_amount']) && $data['amount'] < $payment->metadata['gateway_amount']) {
                Log::warning('Flutterwave payment amount mismatch', [
                    'expected' => $payment->metadata['gateway_amount'],
                    'paid' => $data['amount'],
                ]);
            }

            // Update Payment
            $payment->updateFromPaymentData(new FlutterwavePayment($data, $this->paymentMethod));

            return CallbackResult::success(
                message: 'Payment completed successfully!',
                payment: $payment->fresh()
            );
        } catch (\Throwable $e) {
            Log::error('Flutterwave callback error: '.$e->getMessage());

            return CallbackResult::failed('Payment processing error: '.$e->getMessage());
        }
    }

    public function handleCancelCallback(Request $request): CallbackResult
    {
        $payment = null;
        try {
            $stateId = $request->query('state');
            if ($stateId) {
                $payment = Payment::where('uuid', $stateId)->first();
                if ($payment) {
                    $payment->markAsFailed('Payment cancelled by user');
                }
            }
        } catch (\Throwable $e) {
            Log::error('Flutterwave cancel callback error: '.$e->getMessage());
        }

        return CallbackResult::success(
            message: 'Payment was cancelled.',
            payment: $payment
        );
    }

    /**
     * Setup payment intent for Flutterwave
     */
    public function setupPaymentIntent(Request $request, Payable $payable): array
    {
        $flutterwave = Coderstm::flutterwave();

        if (! $flutterwave) {
            throw new \Exception('Flutterwave client not configured');
        }

        // Ensure the payable supports the required currencies
        $payable->setCurrencies($this->supportedCurrencies());

        // Validate currency
        $this->validateCurrency($payable);

        try {
            // Pre-create Pending Payment
            $currency = strtoupper($payable->getCurrency());
            $amount = $payable->getGatewayAmount();

            $payment = Payment::create([
                'paymentable_type' => $payable->isOrder() ? Coderstm::$orderModel : get_class($payable->getSource()),
                'paymentable_id' => $payable->getSourceId(),
                'payment_method_id' => $this->getPaymentMethodId(),
                'transaction_id' => 'FLW_'.$payable->getReferenceId().'_'.time(), // Pending Ref
                'amount' => $payable->getGrandTotal(),
                'status' => Payment::STATUS_PENDING,
                'note' => 'Flutterwave payment initiated',
                'metadata' => array_merge($payable->getMetadata(), array_filter([
                    'return_url' => $request->input('return_url'),
                ]), [
                    'gateway_currency' => $currency,
                    'gateway_amount' => $amount,
                ]),
            ]);

            $txRef = $payment->transaction_id;

            $payload = [
                'tx_ref' => $txRef,
                'amount' => $amount,
                'currency' => $currency,
                'redirect_url' => $this->getSuccessUrl(['state' => $payment->uuid]),
                'payment_options' => 'card,banktransfer,ussd,account,mobilemoneyghana,mobilemoneyrwanda,mobilemoneyzambia,mobilemoneyuganda,mpesa',
                'customer' => [
                    'email' => $payable->getCustomerEmail(),
                    'phonenumber' => $payable->getCustomerPhone() ?? '',
                    'name' => trim($payable->getCustomerFirstName().' '.$payable->getCustomerLastName()),
                ],
                'customizations' => [
                    'title' => config('app.name').' Payment',
                    'description' => 'Order #'.$payable->getReferenceId(),
                    'logo' => config('app.logo'),
                ],
                'meta' => array_merge(
                    $payable->getMetadata(),
                    ['payment_method_id' => $this->paymentMethod->id]
                ),
            ];

            // Initialize hosted payment via Flutterwave API
            $response = $flutterwave->createPayment($payload);

            if (! empty($response['data']['link'])) {
                return [
                    'success' => true,
                    'payment_url' => $response['data']['link'],
                    'tx_ref' => $txRef,
                    'provider' => 'flutterwave',
                    'amount' => $amount,
                    'currency' => $currency,
                    'state_id' => $payment->uuid,
                ];
            }

            $errorMessage = $response['message'] ?? 'Failed to initialize Flutterwave payment';
            throw new \Exception($errorMessage);
        } catch (\Throwable $e) {
            throw new \Exception('Flutterwave payment setup failed: '.$e->getMessage());
        }
    }

    /**
     * Confirm payment completion for Flutterwave
     */
    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        return PaymentResult::failed('Direct confirmation not supported. Use success callback.');
    }

    /**
     * Handle webhook notifications from Flutterwave
     */
    public function handleWebhook(Request $request): array
    {
        try {
            $payload = $request->all();

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRefund(): bool
    {
        return true;
    }

    /**
     * Process a refund through Flutterwave.
     */
    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        try {
            $flutterwave = Coderstm::flutterwave();

            if (! $flutterwave) {
                return RefundResult::failed('Flutterwave client not configured');
            }

            $refundAmt = $amount ?? ($payment->metadata['gateway_amount'] ?? $payment->amount);

            $response = $flutterwave->refundTransaction($payment->transaction_id, $refundAmt);

            if (! $response || ($response['status'] ?? '') !== 'success') {
                return RefundResult::failed(
                    'Flutterwave refund failed: '.($response['message'] ?? 'Unknown error')
                );
            }

            $refundData = $response['data'] ?? [];

            return RefundResult::success(
                refundId: (string) ($refundData['id'] ?? $payment->transaction_id.'_refund'),
                amount: $payment->amount,
                status: $refundData['status'] ?? 'processed',
                metadata: [
                    'flutterwave_refund_id' => $refundData['id'] ?? null,
                    'gateway_refund_amount' => $refundAmt,
                ]
            );
        } catch (\Throwable $e) {
            return RefundResult::failed('Flutterwave refund error: '.$e->getMessage());
        }
    }
}
