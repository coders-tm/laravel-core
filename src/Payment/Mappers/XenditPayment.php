<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;

class XenditPayment extends AbstractPayment
{
    public function __construct($response, PaymentMethod $paymentMethod)
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $response['transaction_id'] ?? $response['id'] ?? uniqid('xendit_');
        $this->amount = $response['amount'] ?? 0;
        $this->currency = $response['currency'] ?? config('app.currency', 'USD');
        $this->status = match ($response['status'] ?? 'UNKNOWN') {
            'PAID', 'SETTLED', 'SUCCEEDED', 'COMPLETED' => Payment::STATUS_COMPLETED,
            'PENDING', 'PROCESSING' => Payment::STATUS_PROCESSING,
            'FAILED', 'EXPIRED' => Payment::STATUS_FAILED,
            'CANCELLED' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };
        $this->note = "Xendit Payment: {$this->transactionId} (Status: {$this->status})";
        if (isset($response['failure_code'])) {
            $this->note .= " - Failure: {$response['failure_code']}";
        }
        if (isset($response['external_id'])) {
            $this->note .= " - External ID: {$response['external_id']}";
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
        $channelCode = $data['channel_code'] ?? null;
        $normalized['channel_code'] = $channelCode;
        $paymentMethod = $data['payment_method'] ?? null;
        if (is_object($paymentMethod)) {
            $paymentMethod = (string) $paymentMethod;
        }
        $paymentChannel = $data['payment_channel'] ?? null;
        if (isset($data['credit_card_charge_id']) || $paymentMethod === 'CREDIT_CARD') {
            $normalized['payment_method_type'] = 'card';
            $normalized['card_brand'] = strtoupper($data['card_brand'] ?? 'card');
            if ($maskedNumber = $data['masked_card_number'] ?? null) {
                $normalized['last_four'] = substr($maskedNumber, -4);
            }
        } elseif (isset($data['ewallet_type']) || $paymentMethod === 'EWALLET' || in_array($channelCode, ['ID_OVO', 'ID_DANA', 'ID_LINKAJA', 'PH_GCASH', 'PH_PAYMAYA'])) {
            $normalized['payment_method_type'] = 'wallet';
            $normalized['wallet_type'] = $data['ewallet_type'] ?? static::getEwalletFromChannel($channelCode) ?? static::getEwalletFromChannel($paymentChannel);
        } elseif (isset($data['bank_code']) || $paymentMethod === 'BANK_TRANSFER' || $paymentMethod === 'CALLBACK_VIRTUAL_ACCOUNT') {
            $normalized['payment_method_type'] = 'virtual_account';
            $normalized['bank_name'] = static::getBankName($data['bank_code'] ?? $paymentChannel ?? 'UNKNOWN');
        } elseif (isset($data['retail_outlet_name']) || $paymentMethod === 'RETAIL_OUTLET') {
            $normalized['payment_method_type'] = 'retail_outlet';
            $normalized['retail_outlet'] = $data['retail_outlet_name'] ?? $paymentChannel ?? 'Retail Outlet';
        } elseif (isset($data['qr_string']) || $paymentMethod === 'QR_CODE' || $paymentMethod === 'QRIS') {
            $normalized['payment_method_type'] = 'qr_code';
        } elseif (isset($data['direct_debit_type']) || $paymentMethod === 'DIRECT_DEBIT') {
            $normalized['payment_method_type'] = 'direct_debit';
        } elseif ($paymentMethod === 'PAYLATER') {
            $normalized['payment_method_type'] = 'paylater';
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        $type = $metadata['payment_method_type'] ?? null;
        if ($type === 'card' && isset($metadata['card_brand']) && isset($metadata['last_four'])) {
            return "{$metadata['card_brand']} •••• {$metadata['last_four']}";
        }
        if ($type === 'wallet' && isset($metadata['wallet_type'])) {
            return static::formatEwalletName($metadata['wallet_type']);
        }
        if ($type === 'virtual_account' && isset($metadata['bank_name'])) {
            return "Virtual Account ({$metadata['bank_name']})";
        }
        if ($type === 'retail_outlet' && isset($metadata['retail_outlet'])) {
            return $metadata['retail_outlet'];
        }
        if ($type === 'qr_code') {
            return 'QR Code';
        }
        if ($type === 'direct_debit') {
            return 'Direct Debit';
        }
        if ($type === 'paylater') {
            return 'Paylater';
        }
        if ($type) {
            return ucfirst(str_replace('_', ' ', $type));
        }

        return 'Xendit';
    }

    private static function getEwalletFromChannel(?string $channelCode): ?string
    {
        $mapping = ['ID_OVO' => 'OVO', 'ID_DANA' => 'DANA', 'ID_LINKAJA' => 'LinkAja', 'PH_GCASH' => 'GCash', 'PH_PAYMAYA' => 'PayMaya'];

        return $mapping[$channelCode] ?? null;
    }

    private static function formatEwalletName(string $ewallet): string
    {
        $names = ['ID_OVO' => 'OVO', 'ID_DANA' => 'DANA', 'ID_LINKAJA' => 'LinkAja', 'PH_GCASH' => 'GCash', 'PH_PAYMAYA' => 'PayMaya', 'OVO' => 'OVO', 'DANA' => 'DANA', 'LINKAJA' => 'LinkAja'];

        return $names[$ewallet] ?? ucfirst(strtolower($ewallet));
    }

    private static function getBankName(string $bankCode): string
    {
        $banks = ['BCA' => 'BCA', 'BNI' => 'BNI', 'BRI' => 'BRI', 'MANDIRI' => 'Mandiri', 'PERMATA' => 'Permata', 'BSI' => 'BSI', 'CIMB' => 'CIMB Niaga'];

        return $banks[$bankCode] ?? $bankCode;
    }
}
