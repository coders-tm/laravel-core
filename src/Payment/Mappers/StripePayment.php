<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;

class StripePayment extends AbstractPayment
{
    public function __construct($response, PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $response->id;
        $this->amount = $response->amount / 100;
        $this->currency = strtoupper($response->currency ?? config('app.currency', 'USD'));
        $this->status = match ($response->status) {
            'succeeded', 'requires_capture' => Payment::STATUS_COMPLETED,
            'canceled' => Payment::STATUS_CANCELLED,
            'processing', 'requires_action', 'requires_confirmation', 'requires_payment_method' => Payment::STATUS_PROCESSING,
            default => Payment::STATUS_FAILED,
        };
        $this->note = "Stripe Payment Intent: {$this->transactionId} (Status: {$this->status})";
        if (isset($response->last_payment_error)) {
            $this->note .= " - Error: {$response->last_payment_error->message}";
        }
        if (isset($response->next_action->type)) {
            $this->note .= " - Next action: {$response->next_action->type}";
        }
        $this->processedAt = isset($response->created) ? new DateTime("@{$response->created}") : new DateTime;
        $this->metadata = $this->extractMetadata($response);
    }

    protected function extractMetadata($response): array
    {
        $normalized = [];
        $paymentMethodDetails = null;
        $paymentMethodObject = null;
        if (isset($response->payment_method) && is_object($response->payment_method)) {
            $paymentMethodObject = $response->payment_method;
        }
        if (isset($response->charges->data[0]->payment_method_details)) {
            $paymentMethodDetails = $response->charges->data[0]->payment_method_details;
        } elseif (isset($response->payment_method_details)) {
            $paymentMethodDetails = $response->payment_method_details;
        }
        if (! $paymentMethodDetails && isset($response->latest_charge) && is_object($response->latest_charge)) {
            if (isset($response->latest_charge->payment_method_details)) {
                $paymentMethodDetails = $response->latest_charge->payment_method_details;
            }
        }
        if ($paymentMethodObject && ! $paymentMethodDetails) {
            $type = $paymentMethodObject->type ?? 'unknown';
            $normalized['payment_method_type'] = $type;
            if ($type === 'card' && isset($paymentMethodObject->card)) {
                $card = $paymentMethodObject->card;
                $normalized['card_brand'] = ucfirst($card->brand ?? 'card');
                $normalized['last_four'] = $card->last4 ?? null;
                $normalized['exp_month'] = $card->exp_month ?? null;
                $normalized['exp_year'] = $card->exp_year ?? null;
                $normalized['card_funding'] = $card->funding ?? null;
                $normalized['country'] = $card->country ?? null;
                if (isset($card->wallet)) {
                    $walletType = $card->wallet->type ?? 'unknown';
                    $normalized['wallet_type'] = $walletType;
                }
            }
            if ($type === 'us_bank_account' && isset($paymentMethodObject->us_bank_account)) {
                $bank = $paymentMethodObject->us_bank_account;
                $normalized['bank_name'] = $bank->bank_name ?? null;
                $normalized['last_four'] = $bank->last4 ?? null;
                $normalized['account_type'] = $bank->account_type ?? null;
            }
            if ($type === 'sepa_debit' && isset($paymentMethodObject->sepa_debit)) {
                $sepa = $paymentMethodObject->sepa_debit;
                $normalized['bank_name'] = $sepa->bank_code ?? null;
                $normalized['last_four'] = $sepa->last4 ?? null;
                $normalized['country'] = $sepa->country ?? null;
            }
        }
        if (! $paymentMethodDetails && empty($normalized)) {
            $normalized['payment_method'] = 'Stripe';

            return $normalized;
        }
        if ($paymentMethodDetails && empty($normalized)) {
            $type = $paymentMethodDetails->type ?? 'unknown';
            $normalized['payment_method_type'] = $type;
            if ($type === 'card' && isset($paymentMethodDetails->card)) {
                $card = $paymentMethodDetails->card;
                $normalized['card_brand'] = ucfirst($card->brand ?? 'card');
                $normalized['last_four'] = $card->last4 ?? null;
                $normalized['exp_month'] = $card->exp_month ?? null;
                $normalized['exp_year'] = $card->exp_year ?? null;
                $normalized['card_funding'] = $card->funding ?? null;
                $normalized['country'] = $card->country ?? null;
                if (isset($card->wallet)) {
                    $walletType = $card->wallet->type ?? 'unknown';
                    $normalized['wallet_type'] = $walletType;
                }
            }
            if ($type === 'us_bank_account' && isset($paymentMethodDetails->us_bank_account)) {
                $bank = $paymentMethodDetails->us_bank_account;
                $normalized['bank_name'] = $bank->bank_name ?? null;
                $normalized['last_four'] = $bank->last4 ?? null;
                $normalized['account_type'] = $bank->account_type ?? null;
            }
            if ($type === 'sepa_debit' && isset($paymentMethodDetails->sepa_debit)) {
                $sepa = $paymentMethodDetails->sepa_debit;
                $normalized['bank_name'] = $sepa->bank_code ?? null;
                $normalized['last_four'] = $sepa->last4 ?? null;
                $normalized['country'] = $sepa->country ?? null;
            }
        }
        if (isset($response->charges->data[0]->receipt_url)) {
            $normalized['receipt_url'] = $response->charges->data[0]->receipt_url;
        } elseif (isset($response->latest_charge) && is_object($response->latest_charge) && isset($response->latest_charge->receipt_url)) {
            $normalized['receipt_url'] = $response->latest_charge->receipt_url;
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        if (isset($metadata['wallet_type'], $metadata['card_brand'], $metadata['last_four'])) {
            $walletName = ucwords(str_replace('_', ' ', $metadata['wallet_type']));

            return "{$walletName} ({$metadata['card_brand']} •••• {$metadata['last_four']})";
        }
        if (isset($metadata['card_brand'], $metadata['last_four'])) {
            return "{$metadata['card_brand']} •••• {$metadata['last_four']}";
        }
        if (isset($metadata['bank_name'], $metadata['last_four'])) {
            return "{$metadata['bank_name']} •••• {$metadata['last_four']}";
        }
        if (isset($metadata['payment_method_type']) && $metadata['payment_method_type'] === 'sepa_debit' && isset($metadata['last_four'])) {
            return "SEPA Direct Debit •••• {$metadata['last_four']}";
        }
        if (isset($metadata['payment_method_type']) && $metadata['payment_method_type'] === 'klarna') {
            return 'Klarna';
        }
        if (isset($metadata['payment_method_type']) && $metadata['payment_method_type'] === 'afterpay_clearpay') {
            return 'Afterpay / Clearpay';
        }
        if (isset($metadata['payment_method_type']) && $metadata['payment_method_type'] === 'alipay') {
            return 'Alipay';
        }
        if (! empty($metadata['payment_method_type'])) {
            return ucfirst(str_replace('_', ' ', $metadata['payment_method_type']));
        }

        return 'Stripe';
    }
}
