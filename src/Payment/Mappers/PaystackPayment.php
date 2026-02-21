<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;

class PaystackPayment extends AbstractPayment
{
    public function __construct($response, PaymentMethod $paymentMethod)
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        $this->paymentMethod = $paymentMethod;
        $transactionData = $response['data'] ?? $response;
        $this->transactionId = $transactionData['reference'] ?? uniqid('paystack_');
        $this->amount = ($transactionData['amount'] ?? 0) / 100;
        $this->currency = strtoupper($transactionData['currency'] ?? config('app.currency', 'USD'));
        $this->status = match ($response['status'] ?? 'unknown') {
            'success', 'successful' => Payment::STATUS_COMPLETED,
            'pending', 'ongoing' => Payment::STATUS_PROCESSING,
            'failed', 'abandoned' => Payment::STATUS_FAILED,
            'cancelled' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };
        $this->note = "Paystack Transaction: {$this->transactionId} (Status: {$this->status})";
        if (isset($response['gateway_response'])) {
            $this->note .= " - {$response['gateway_response']}";
        }
        $this->processedAt = new DateTime;
        $this->metadata = $this->extractMetadata($response);
    }

    protected function extractMetadata($data): array
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        $normalized = [];
        $transactionData = $data['data'] ?? $data;
        $channel = $transactionData['channel'] ?? null;
        $normalized['payment_method_type'] = $channel;
        if (isset($transactionData['authorization'])) {
            $auth = $transactionData['authorization'];
            $normalized['card_brand'] = ucfirst($auth['card_type'] ?? 'card');
            $normalized['last_four'] = $auth['last4'] ?? null;
            $normalized['bank_name'] = $auth['bank'] ?? null;
            $normalized['country'] = $auth['country_code'] ?? null;
            if (isset($auth['reusable'])) {
                $normalized['reusable'] = $auth['reusable'];
            }
        }
        if ($channel === 'mobile_money' && isset($transactionData['authorization']['mobile_money_number'])) {
            $normalized['wallet_type'] = 'mobile_money';
            $normalized['mobile_number'] = $transactionData['authorization']['mobile_money_number'];
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        $channel = $metadata['payment_method_type'] ?? null;
        if ($channel === 'card' && isset($metadata['card_brand']) && isset($metadata['last_four'])) {
            $display = "{$metadata['card_brand']} •••• {$metadata['last_four']}";
            if (isset($metadata['bank_name'])) {
                $display .= " ({$metadata['bank_name']})";
            }

            return $display;
        }
        if ($channel === 'mobile_money') {
            if (isset($metadata['mobile_number'])) {
                return "Mobile Money ({$metadata['mobile_number']})";
            }

            return 'Mobile Money';
        }
        if ($channel === 'ussd') {
            return 'USSD'.(isset($metadata['bank_name']) ? " ({$metadata['bank_name']})" : '');
        }
        if ($channel === 'bank_transfer') {
            return 'Bank Transfer'.(isset($metadata['bank_name']) ? " ({$metadata['bank_name']})" : '');
        }
        if ($channel === 'bank') {
            return 'Bank'.(isset($metadata['bank_name']) ? " ({$metadata['bank_name']})" : '');
        }
        if ($channel === 'qr') {
            return 'QR Code';
        }
        if ($channel) {
            return ucfirst(str_replace('_', ' ', $channel));
        }

        return 'Paystack';
    }
}
