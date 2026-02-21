<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Mappers\PayPalPayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;

class PaypalProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    private const SUPPORTED_CURRENCIES = ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD'];

    public function getProvider(): string
    {
        return PaymentMethod::PAYPAL;
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    protected function isCurrencySupported(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supportedCurrencies());
    }

    public function setupPaymentIntent(Request $request, Payable $payable): array
    {
        $payable->setCurrencies($this->supportedCurrencies());
        $this->validateCurrency($payable);
        $currency = $payable->getCurrency();
        $paypal = Coderstm::paypal();
        $order = $paypal->createOrder(['intent' => 'CAPTURE', 'purchase_units' => [['amount' => ['currency_code' => $currency, 'value' => number_format($payable->getGatewayAmount(), 2, '.', '')], 'description' => 'Payment for '.($payable->isCheckout() ? 'checkout' : "order #{$payable->getReferenceId()}"), 'reference_id' => $payable->getReferenceId()]], 'application_context' => ['return_url' => $this->getSuccessUrl(), 'cancel_url' => $this->getCancelUrl()]]);
        if (isset($order['error'])) {
            $error = $order['error'];
            if (isset($error['details'][0]['issue'])) {
                $issue = $error['details'][0]['issue'];
                $description = $error['details'][0]['description'] ?? 'Unknown error';
                if ($issue === 'CURRENCY_NOT_SUPPORTED') {
                    throw new \Exception("Currency '{$currency}' is not supported. Supported currencies: ".implode(', ', $this->supportedCurrencies()));
                }
                throw new \Exception("PayPal error ({$issue}): {$description}");
            }
            throw new \Exception($error['message'] ?? 'Unknown error occurred');
        }
        if (! isset($order['id'])) {
            throw new \Exception('Failed to create PayPal order: No order ID returned');
        }
        $status = $order['status'] ?? 'CREATED';
        if ($status !== 'CREATED') {
            throw new \Exception('Failed to create PayPal order: Unexpected status: '.$status);
        }

        return ['paypal_order_id' => $order['id'], 'status' => $status, 'amount' => $payable->getGatewayAmount(), 'currency' => $payable->getCurrency()];
    }

    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        $request->validate(['paypal_order_id' => 'required|string', 'payer_id' => 'required|string']);
        try {
            $paypal = Coderstm::paypal();
            $capture = $paypal->capturePaymentOrder($request->paypal_order_id);
            if (isset($capture['error'])) {
                $error = $capture['error'];
                $message = $error['message'] ?? __('PayPal API error');
                if (isset($error['details']) && is_array($error['details'])) {
                    foreach ($error['details'] as $detail) {
                        if (isset($detail['issue']) && $detail['issue'] === 'ORDER_ALREADY_CAPTURED') {
                            PaymentResult::failed(__('Payment already captured. This PayPal order cannot be confirmed again.'), ['paypal_order_id' => $request->paypal_order_id]);
                        }
                    }
                }
                PaymentResult::failed("PayPal payment capture failed: {$message}");
            }
            if (! isset($capture['status'])) {
                PaymentResult::failed('PayPal payment capture failed: Invalid response structure');
            }
            if ($capture['status'] !== 'COMPLETED') {
                PaymentResult::failed('PayPal payment capture failed: '.($capture['message'] ?? "Status: {$capture['status']}"));
            }
            $paymentData = new PayPalPayment($capture, $this->paymentMethod);

            return PaymentResult::success(paymentData: $paymentData, transactionId: $paymentData->getTransactionId(), status: 'success');
        } catch (\Throwable $e) {
            report($e);
            PaymentResult::failed($e->getMessage());
        }
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        try {
            $paypal = Coderstm::paypal();
            $captureId = $payment->metadata['capture_id'] ?? $payment->transaction_id;
            if (! $captureId) {
                RefundResult::failed('Cannot process refund: Missing PayPal capture ID');
            }
            $refundAmount = $amount ?? $payment->amount;
            $invoiceId = $payment->metadata['invoice_id'] ?? "REFUND-{$payment->id}";
            $note = $reason ?? 'Refund requested';
            $refund = $paypal->refundCapturedPayment($captureId, $invoiceId, $refundAmount, substr($note, 0, 255));
            if (isset($refund['error'])) {
                $error = $refund['error'];
                RefundResult::failed('PayPal refund error: '.($error['message'] ?? 'Unknown error'));
            }
            if (! isset($refund['id']) || ! in_array($refund['status'], ['COMPLETED', 'PENDING'])) {
                RefundResult::failed('PayPal refund failed with status: '.($refund['status'] ?? 'unknown'));
            }
            $refundedAmount = (float) ($refund['amount']['value'] ?? $refundAmount);

            return RefundResult::success(refundId: $refund['id'], amount: $refundedAmount, status: strtolower($refund['status']), metadata: ['paypal_refund_id' => $refund['id'], 'capture_id' => $captureId, 'status' => $refund['status']]);
        } catch (\Throwable $e) {
            RefundResult::failed('PayPal refund error: '.$e->getMessage());
        }
    }
}
