<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;

class PayPalPayment extends AbstractPayment
{
    public function __construct($response, PaymentMethod $paymentMethod)
    {
        if (is_array($response)) {
            $response = json_decode(json_encode($response));
        }
        $this->paymentMethod = $paymentMethod;
        if (isset($response->purchase_units[0]->payments->captures[0]->id)) {
            $this->transactionId = $response->purchase_units[0]->payments->captures[0]->id;
        } else {
            $this->transactionId = $response->id ?? uniqid('pp_');
        }
        if (isset($response->purchase_units[0]->payments->captures[0]->amount)) {
            $amount = $response->purchase_units[0]->payments->captures[0]->amount;
            $this->amount = $amount->value;
            $this->currency = $amount->currency_code;
        } elseif (isset($response->purchase_units[0]->amount)) {
            $amount = $response->purchase_units[0]->amount;
            $this->amount = $amount->value;
            $this->currency = $amount->currency_code;
        } else {
            $this->amount = 0;
            $this->currency = config('app.currency', 'USD');
        }
        $this->status = match (strtoupper($response->status ?? 'CREATED')) {
            'COMPLETED', 'APPROVED' => Payment::STATUS_COMPLETED,
            'VOIDED', 'CANCELLED' => Payment::STATUS_CANCELLED,
            'CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED' => Payment::STATUS_PROCESSING,
            default => Payment::STATUS_FAILED,
        };
        $this->note = "PayPal Order: {$response->id} (Status: {$this->status})";
        $this->processedAt = isset($response->create_time) ? new DateTime($response->create_time) : new DateTime;
        $this->metadata = $this->extractMetadata($response);
    }

    protected function extractMetadata($response): array
    {
        if (is_array($response)) {
            $response = json_decode(json_encode($response));
        }
        $normalized = ['payment_method_type' => 'wallet', 'wallet_type' => 'paypal', 'paypal_order_id' => $response->id ?? null];
        if (isset($response->payer)) {
            $payer = $response->payer;
            $normalized['payer_email'] = $payer->email_address ?? null;
            if (isset($payer->name)) {
                $normalized['payer_name'] = trim(($payer->name->given_name ?? '').' '.($payer->name->surname ?? ''));
            }
        }
        if (isset($response->payment_source)) {
            $paymentSource = $response->payment_source;
            if (isset($paymentSource->paypal)) {
                $normalized['payer_email'] = $paymentSource->paypal->email_address ?? $normalized['payer_email'] ?? null;
            }
            if (isset($paymentSource->card)) {
                $card = $paymentSource->card;
                $normalized['payment_method_type'] = 'card';
                $brand = $card->brand ?? 'card';
                $normalized['card_brand'] = ucfirst(strtolower($brand));
                $normalized['last_four'] = substr($card->last_digits ?? '', -4);
            } elseif (isset($paymentSource->venmo)) {
                $normalized['payment_method_type'] = 'wallet';
                $normalized['wallet_type'] = 'venmo';
                $normalized['venmo_username'] = $paymentSource->venmo->user_name ?? null;
            }
        }
        if (isset($response->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value)) {
            $normalized['transaction_fee'] = $response->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value;
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        if (isset($metadata['payment_method_type']) && $metadata['payment_method_type'] === 'card' && isset($metadata['card_brand']) && isset($metadata['last_four'])) {
            return "{$metadata['card_brand']} •••• {$metadata['last_four']} (via PayPal)";
        }
        if (isset($metadata['wallet_type']) && $metadata['wallet_type'] === 'venmo') {
            return 'Venmo'.(isset($metadata['venmo_username']) ? " (@{$metadata['venmo_username']})" : '');
        }
        if (isset($metadata['payer_email'])) {
            return "PayPal ({$metadata['payer_email']})";
        }

        return 'PayPal';
    }
}
