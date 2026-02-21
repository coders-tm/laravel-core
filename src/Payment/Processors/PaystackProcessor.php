<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\CallbackResult;
use Coderstm\Payment\Mappers\PaystackPayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    private const SUPPORTED_CURRENCIES = ['NGN', 'GHS', 'ZAR', 'USD'];

    public function getProvider(): string
    {
        return PaymentMethod::PAYSTACK;
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function validateCallback(Request $request): ?string
    {
        $checkoutToken = $request->get('reference') ?? $request->get('checkout_token');
        if ($checkoutToken) {
            Log::info('Paystack callback validation successful', ['reference' => $request->get('reference'), 'trxref' => $request->get('trxref'), 'status' => $request->get('status')]);
        }

        return $checkoutToken;
    }

    public function handleSuccessCallback(Request $request): CallbackResult
    {
        try {
            $stateId = $request->query('state');
            if (! $stateId) {
                Log::error('Paystack success callback: Missing state parameter');

                return CallbackResult::failed('Invalid payment session.');
            }
            $payment = Payment::where('uuid', $stateId)->first();
            if (! $payment) {
                Log::error('Paystack success callback: Payment not found', ['state_id' => $stateId]);

                return CallbackResult::failed('Payment session expired or not found.');
            }
            $reference = $request->input('reference') ?? $request->input('trxref');
            if (! $reference) {
                return CallbackResult::failed('Missing transaction reference.');
            }
            $paystack = Coderstm::paystack();
            $response = $paystack->transaction->verify(['reference' => $reference]);
            if (! $response->status) {
                return CallbackResult::failed($response['message'] ?? 'Transaction verification failed');
            }
            $data = $response->data;
            if ($data->status !== 'success') {
                return CallbackResult::failed('Payment not successful: '.$data->status);
            }
            $expectedKobo = round($payment->metadata['gateway_amount'] * 100);
            if ($data->amount != $expectedKobo) {
                Log::warning('Paystack payment amount mismatch', ['expected_kobo' => $expectedKobo, 'paid_kobo' => $data->amount]);
            }
            $paymentData = new PaystackPayment($data, $this->paymentMethod);
            $payment->update($paymentData->toArray());

            return CallbackResult::success(message: 'Payment completed successfully!', payment: $payment->fresh());
        } catch (\Throwable $e) {
            Log::error('Paystack callback error: '.$e->getMessage());

            return CallbackResult::failed('Payment error: '.$e->getMessage());
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
        $paystack = Coderstm::paystack();
        $payable->setCurrencies($this->supportedCurrencies());
        $this->validateCurrency($payable);
        $gatewayCurrency = strtoupper($payable->getCurrency());
        $amount = $payable->getGrandTotal();
        $gatewayAmount = $payable->getGatewayAmount();
        $payment = Payment::create(['paymentable_type' => $payable->isOrder() ? Coderstm::$orderModel : get_class($payable->getSource()), 'paymentable_id' => $payable->getSourceId(), 'payment_method_id' => $this->getPaymentMethodId(), 'transaction_id' => 'PAYSTACK_'.$payable->getReferenceId().'_'.time(), 'amount' => $amount, 'status' => Payment::STATUS_PENDING, 'note' => 'Paystack payment initiated', 'metadata' => array_merge($payable->getMetadata(), ['gateway_currency' => $gatewayCurrency, 'gateway_amount' => $gatewayAmount])]);
        Log::info('Paystack payment setup', ['payment' => $payment, 'gateway_amount' => $gatewayAmount, 'gateway_currency' => $gatewayCurrency]);
        $reference = $payment->transaction_id;
        $transactionData = ['email' => $payable->getCustomerEmail(), 'amount' => $gatewayAmount * 100, 'currency' => $gatewayCurrency, 'reference' => $reference, 'callback_url' => $this->getSuccessUrl(['state' => $payment->uuid]), 'metadata' => array_merge($payable->getMetadata(), ['customer_name' => $payable->getCustomerFirstName().' '.$payable->getCustomerLastName(), 'customer_phone' => $payable->getCustomerPhone(), 'cancel_action' => $this->getCancelUrl(['state' => $payment->uuid])]), 'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer']];
        try {
            $response = $paystack->transaction->initialize($transactionData);
            if (! $response->status) {
                throw new \Exception('Failed to initialize Paystack transaction: '.$response->message);
            }

            return ['authorization_url' => $response->data->authorization_url, 'access_code' => $response->data->access_code, 'reference' => $response->data->reference, 'amount' => $gatewayAmount, 'currency' => $gatewayCurrency, 'state_id' => $payment->uuid];
        } catch (\Throwable $e) {
            throw new \Exception('Failed to create Paystack transaction: '.$e->getMessage());
        }
    }

    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        return PaymentResult::failed('Direct confirmation not supported. Use success callback.');
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        try {
            $paystack = Coderstm::paystack();
            $payload = ['transaction' => $payment->transaction_id];
            if ($reason) {
                $payload['customer_note'] = $reason;
                $payload['merchant_note'] = $reason;
            }
            $response = $paystack->refund->create($payload);
            if (! $response->status) {
                RefundResult::failed('Paystack refund failed: '.$response->message);
            }
            $data = $response->data;

            return RefundResult::success(refundId: (string) $data->id, amount: $payment->amount, status: $data->status, metadata: ['paystack_refund_id' => $data->id, 'transaction_ref' => $data->transaction->reference ?? null]);
        } catch (\Throwable $e) {
            RefundResult::failed('Paystack refund error: '.$e->getMessage());
        }
    }
}
