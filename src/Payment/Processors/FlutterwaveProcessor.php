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
            $txRef = $request->input('tx_ref');
            $transactionId = $request->input('transaction_id');
            if (! $transactionId && ! $txRef) {
                return CallbackResult::failed('Missing transaction reference.');
            }
            $flutterwave = Coderstm::flutterwave();
            $response = $flutterwave->requeryTransaction($transactionId ?? $txRef);
            if (! $response || $response['status'] !== 'success') {
                return CallbackResult::failed('Payment verification failed at gateway.');
            }
            $data = $response['data'];
            if ($data['amount'] < $payment->metadata['gateway_amount']) {
                Log::warning('Flutterwave payment amount mismatch', ['expected' => $payment->metadata['gateway_amount'], 'paid' => $data['amount']]);
            }
            $paymentData = new FlutterwavePayment($data, $this->paymentMethod);
            $payment->update($paymentData->toArray());

            return CallbackResult::success(message: 'Payment completed successfully!', payment: $payment->fresh());
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
        }

        return CallbackResult::success(message: 'Payment was cancelled.', payment: $payment);
    }

    public function setupPaymentIntent(Request $request, Payable $payable): array
    {
        $flutterwave = Coderstm::flutterwave();
        if (! $flutterwave) {
            throw new \Exception('Flutterwave client not configured');
        }
        $payable->setCurrencies($this->supportedCurrencies());
        $this->validateCurrency($payable);
        try {
            $currency = $payable->getCurrency();
            $amount = $payable->getGatewayAmount();
            $payment = Payment::create(['paymentable_type' => $payable->isOrder() ? Coderstm::$orderModel : get_class($payable->getSource()), 'paymentable_id' => $payable->getSourceId(), 'payment_method_id' => $this->getPaymentMethodId(), 'transaction_id' => 'FLW_'.$payable->getReferenceId().'_'.time(), 'amount' => $amount, 'status' => Payment::STATUS_PENDING, 'note' => 'Flutterwave payment initiated', 'metadata' => array_merge($payable->getMetadata(), ['gateway_currency' => $currency, 'gateway_amount' => $amount])]);
            $txRef = $payment->transaction_id;
            $payload = ['tx_ref' => $txRef, 'amount' => $amount, 'currency' => $currency, 'redirect_url' => $this->getSuccessUrl(['state' => $payment->uuid]), 'payment_options' => 'card,banktransfer,ussd,account', 'customer' => ['email' => $payable->getCustomerEmail(), 'phonenumber' => $payable->getCustomerPhone() ?? '', 'name' => $payable->getCustomerFirstName().' '.$payable->getCustomerLastName()], 'customizations' => ['title' => config('app.name').' Payment', 'description' => 'Order #'.$payable->getReferenceId(), 'logo' => config('app.logo')], 'meta' => array_merge($payable->getMetadata(), ['payment_method_id' => $this->paymentMethod->id])];
            $paymentUrl = $flutterwave->setAmount($payload['amount'])->setCurrency($payload['currency'])->setEmail($payload['customer']['email'])->setFirstname($payable->getCustomerFirstName())->setLastname($payable->getCustomerLastName())->setPhoneNumber($payload['customer']['phonenumber'] ?? '')->setTitle($payload['customizations']['title'])->setDescription($payload['customizations']['description'])->setRedirectUrl($payload['redirect_url'])->setMetaData($payload['meta'])->initialize();
            if ($paymentUrl) {
                return ['success' => true, 'payment_url' => $paymentUrl, 'tx_ref' => $txRef, 'provider' => 'flutterwave', 'amount' => $amount, 'currency' => $currency, 'state_id' => $payment->uuid];
            }
            throw new \Exception('Failed to initialize Flutterwave payment');
        } catch (\Throwable $e) {
            throw new \Exception('Flutterwave payment setup failed: '.$e->getMessage());
        }
    }

    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        return PaymentResult::failed('Direct confirmation not supported. Use success callback.');
    }

    public function handleWebhook(Request $request): array
    {
        try {
            $payload = $request->all();

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        try {
            $flutterwave = Coderstm::flutterwave();
            if (! $flutterwave) {
                RefundResult::failed('Flutterwave client not configured');
            }
            $refundAmt = $payment->metadata['gateway_amount'] ?? $payment->amount;
            $transactionService = new \Flutterwave\Service\Transactions;
            $response = $transactionService->refund($payment->transaction_id);
            if (! $response || $response->status !== 'success') {
                RefundResult::failed('Flutterwave refund failed: '.($response->message ?? 'Unknown error'));
            }
            $refundData = $response->data ?? new \stdClass;

            return RefundResult::success(refundId: (string) ($refundData->id ?? $payment->transaction_id.'_refund'), amount: $payment->amount, status: $refundData->status ?? 'processed', metadata: ['flutterwave_refund_id' => $refundData->id ?? null, 'gateway_refund_amount' => $refundAmt]);
        } catch (\Throwable $e) {
            RefundResult::failed('Flutterwave refund error: '.$e->getMessage());
        }
    }
}
