<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;

class RazorpayPayment extends AbstractPayment
{
    public function __construct($response, PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $response->id ?? uniqid('rzp_');
        $this->amount = ($response->amount ?? 0) / 100;
        $this->currency = strtoupper($response->currency ?? config('app.currency', 'USD'));
        $this->status = match ($response->status ?? 'created') {
            'captured', 'authorized' => Payment::STATUS_COMPLETED,
            'failed' => Payment::STATUS_FAILED,
            'refunded' => Payment::STATUS_REFUNDED,
            default => Payment::STATUS_PROCESSING,
        };
        $this->note = "Razorpay Payment: {$this->transactionId} (Status: {$this->status})";
        if (isset($response->error_description)) {
            $this->note .= " - {$response->error_description}";
        }
        $this->processedAt = isset($response->created_at) ? new DateTime("@{$response->created_at}") : new DateTime;
        $this->metadata = $this->extractMetadata($response);
    }

    protected function extractMetadata($response): array
    {
        $normalized = [];
        $method = $response->method ?? null;
        $normalized['payment_method_type'] = $method;
        if (isset($response->card)) {
            $card = $response->card;
            $normalized['card_brand'] = ucfirst($card->network ?? 'card');
            $normalized['last_four'] = $card->last4 ?? null;
            $normalized['issuer'] = $card->issuer ?? null;
            $normalized['card_type'] = $card->type ?? null;
        }
        if (isset($response->vpa)) {
            $normalized['upi_id'] = $response->vpa;
            $normalized['wallet_type'] = 'upi';
        }
        if ($method === 'wallet' && isset($response->wallet)) {
            $normalized['wallet_type'] = strtolower($response->wallet);
        }
        if ($method === 'netbanking' && isset($response->bank)) {
            $normalized['bank_name'] = strtoupper($response->bank);
        }
        if (isset($response->emi) && $response->emi) {
            $normalized['emi_duration'] = $data['emi_duration'] ?? null;
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        $method = $metadata['payment_method_type'] ?? null;
        if ($method === 'card' && isset($metadata['card_brand']) && isset($metadata['last_four'])) {
            $display = "{$metadata['card_brand']} •••• {$metadata['last_four']}";
            if (isset($metadata['issuer'])) {
                $display .= " ({$metadata['issuer']})";
            }
            if (! empty($metadata['emi_duration'])) {
                $display .= " - EMI ({$metadata['emi_duration']} months)";
            }

            return $display;
        }
        if ($method === 'upi' && isset($metadata['upi_id'])) {
            return "UPI ({$metadata['upi_id']})";
        }
        if ($method === 'wallet' && isset($metadata['wallet_type'])) {
            return ucfirst($metadata['wallet_type']).' Wallet';
        }
        if ($method === 'netbanking' && isset($metadata['bank_name'])) {
            return "Net Banking ({$metadata['bank_name']})";
        }
        if ($method) {
            return ucfirst(str_replace('_', ' ', $method));
        }

        return 'Razorpay';
    }
}
