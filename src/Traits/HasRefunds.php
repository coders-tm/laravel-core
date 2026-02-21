<?php

namespace Coderstm\Traits;

use Coderstm\Exceptions\RefundException;
use Coderstm\Models\Payment;
use Illuminate\Support\Facades\DB;

trait HasRefunds
{
    public function refund(?string $reason = null, bool $toWallet = false)
    {
        if ($toWallet) {
            return $this->refundToWallet($reason);
        }
        $payments = $this->payments()->where('status', Payment::STATUS_COMPLETED)->where(function ($query) {
            $query->whereNull('refund_amount')->orWhereRaw('amount > COALESCE(refund_amount, 0)');
        })->orderBy('created_at', 'desc')->get();
        if ($payments->isEmpty()) {
            return $this->refundToWallet($reason);
        }
        $lastRefund = null;
        foreach ($payments as $payment) {
            if ($payment->refundable_amount <= 0) {
                continue;
            }
            try {
                $isWallet = $payment->paymentMethod?->provider === \Coderstm\Models\PaymentMethod::WALLET;
                if ($isWallet) {
                    $lastRefund = $this->refundPaymentToWallet($payment, $reason);
                } else {
                    $lastRefund = $this->refundToOriginalPayment($payment, $reason);
                }
            } catch (RefundException $e) {
                if ($e->isNotSupported()) {
                    $lastRefund = $this->refundPaymentToWallet($payment, $reason);
                } else {
                    throw $e;
                }
            }
        }

        return $lastRefund;
    }

    public function refundToWallet(?string $reason = null)
    {
        if (! $this->customer) {
            throw new \Exception('Cannot refund to wallet: Order has no customer.');
        }
        $refundAmount = $this->refundable_amount;
        if ($refundAmount <= 0) {
            throw new \Exception('Refund amount must be greater than zero.');
        }

        return DB::transaction(function () use ($refundAmount, $reason) {
            $transaction = $this->customer->creditWallet(amount: $refundAmount, source: 'refund', description: "Refund for order {$this->formated_id}".($reason ? ": {$reason}" : ''), transactionable: $this, metadata: ['order_id' => $this->id, 'order_number' => $this->formated_id, 'refund_reason' => $reason]);
            $refund = $this->refunds()->create(['amount' => $refundAmount, 'reason' => $reason, 'to_wallet' => true, 'wallet_transaction_id' => $transaction->id]);
            $this->syncCurrentStatus();
            event(new \Coderstm\Events\RefundProcessed($refund));

            return $refund;
        });
    }

    protected function refundPaymentToWallet(Payment $payment, ?string $reason = null)
    {
        if (! $this->customer) {
            throw new \Exception('Cannot refund to wallet: Order has no customer.');
        }
        $refundAmount = $payment->refundable_amount;
        if ($refundAmount <= 0) {
            throw new \Exception('Payment amount must be greater than zero to refund.');
        }

        return DB::transaction(function () use ($payment, $refundAmount, $reason) {
            $transaction = $this->customer->creditWallet(amount: $refundAmount, source: 'refund', description: "Refund for payment #{$payment->transaction_id}".($reason ? ": {$reason}" : ''), transactionable: $this, metadata: ['order_id' => $this->id, 'order_number' => $this->formated_id, 'payment_id' => $payment->id, 'refund_reason' => $reason]);
            $refund = $this->refunds()->create(['amount' => $refundAmount, 'reason' => $reason, 'payment_id' => $payment->id, 'to_wallet' => true, 'wallet_transaction_id' => $transaction->id]);
            $payment->processRefund($reason);
            $this->syncCurrentStatus();
            event(new \Coderstm\Events\RefundProcessed($refund));

            return $refund;
        });
    }

    public function refundToOriginalPayment(Payment $payment, ?string $reason = null)
    {
        $refundAmount = $payment->refundable_amount;
        if ($refundAmount <= 0) {
            throw new \Exception('Refund amount must be greater than zero.');
        }
        if ($refundAmount > $this->refundable_amount) {
            throw new \Exception('Refund amount exceeds order refundable amount.');
        }
        $paymentMethod = $payment->paymentMethod;
        if (! $paymentMethod) {
            throw new \Exception('Payment method not found for this payment.');
        }
        $processor = \Coderstm\Payment\Processor::make($paymentMethod->provider);
        $processor->setPaymentMethod($paymentMethod);
        if (! $processor->supportsRefund()) {
            throw new \Coderstm\Exceptions\RefundException("Refunds are not supported for the {$paymentMethod->provider} payment method. "."Use 'Refund to Wallet' option to credit the customer's wallet balance.", ['error_type' => 'not_supported']);
        }

        return DB::transaction(function () use ($processor, $payment, $refundAmount, $reason) {
            $refundResult = $processor->refund($payment, $refundAmount, $reason);
            $payment->processRefund($reason);
            $refund = $this->refunds()->create(['amount' => $refundAmount, 'reason' => $reason, 'payment_id' => $payment->id, 'to_wallet' => false, 'metadata' => $refundResult->toArray()]);
            $this->syncCurrentStatus();

            return $refund;
        });
    }
}
