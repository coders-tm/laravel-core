<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    private const SUPPORTED_CURRENCIES = [];

    public function getProvider(): string
    {
        return PaymentMethod::WALLET;
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function setupPaymentIntent(Request $request, Payable $payable): array
    {
        $user = $request->user();
        if (! $user) {
            throw ValidationException::withMessages(['user' => 'User must be authenticated to use wallet payment.']);
        }
        $amount = $payable->getGrandTotal();
        $currency = config('app.currency', 'USD');
        $walletBalance = $user->getWalletBalance();

        return ['message' => 'Wallet payment ready', 'amount' => $amount, 'currency' => $currency, 'wallet_balance' => $walletBalance, 'formatted_balance' => format_amount($walletBalance, $currency), 'has_sufficient_balance' => $user->hasWalletBalance($amount)];
    }

    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        $user = $request->user();
        if (! $user) {
            PaymentResult::failed('Wallet payment cannot be processed. Only customer wallet payments are supported at this time.');
        }
        $amount = $payable->getGrandTotal();
        $currency = config('app.currency', 'USD');
        if (! $user->hasWalletBalance($amount)) {
            $available = $user->getWalletBalance();
            PaymentResult::failed('Insufficient wallet balance. Required: '.format_amount($amount, $currency).', Available: '.format_amount($available, $currency));
        }
        try {
            $description = $this->getTransactionDescription($payable);
            $source = $payable->getSource();
            $transaction = $user->debitWallet(amount: $amount, source: 'payment', description: $description, transactionable: $source, metadata: ['payable_type' => $source ? get_class($source) : $payable->getType(), 'payable_id' => $source?->id ?? $payable->getReferenceId()]);

            return PaymentResult::success(paymentData: null, transactionId: (string) $transaction->id, status: 'succeeded', metadata: ['payment_method' => 'wallet', 'wallet_transaction_id' => $transaction->id, 'previous_balance' => $transaction->balance_before, 'current_balance' => $transaction->balance_after, 'amount' => $transaction->amount]);
        } catch (\Throwable $e) {
            PaymentResult::failed('Wallet payment failed: '.$e->getMessage());
        }
    }

    protected function getTransactionDescription(Payable $payable): string
    {
        $source = $payable->getSource();
        if (! $source) {
            return ucfirst($payable->getType()).' payment';
        }
        $modelClass = class_basename($source);

        return match (true) {
            $source instanceof \Coderstm\Models\Subscription => "Subscription payment - {$source->plan->label}",
            $source instanceof \Coderstm\Models\Shop\Order => "Order payment - Order #{$source->formated_id}",
            default => "{$modelClass} payment - ID #{$source->id}",
        };
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        RefundResult::notSupported('Wallet payments cannot be refunded to the original payment method. '.'Use the "Refund to Wallet" option to credit the customer\'s wallet balance.');
    }
}
