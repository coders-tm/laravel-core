<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;
use Illuminate\Support\Str;

class ManualPayment extends AbstractPayment
{
    public function __construct(array $response, PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $response['transaction_id'] ?? $response['reference_number'] ?? 'MANUAL_'.Str::upper(Str::random(10));
        $this->amount = $response['amount'] ?? 0;
        $this->currency = $response['currency'] ?? config('app.currency', 'USD');
        $this->status = $response['status'] ?? Payment::STATUS_PENDING;
        $this->note = $response['note'] ?? null;
        $this->processedAt = new DateTime;
        $this->metadata = $this->extractMetadata($response);
    }

    public static function withReference(string $referenceNumber, ?PaymentMethod $paymentMethod = null, ?string $note = null, array $additionalData = []): self
    {
        $paymentData = array_merge($additionalData, ['reference_number' => $referenceNumber, 'note' => $note]);

        return new self($paymentData, $paymentMethod);
    }

    protected function extractMetadata($data): array
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        $normalized = [];
        $paymentType = $data['payment_type'] ?? 'manual';
        $normalized['payment_method_type'] = $paymentType;
        if (isset($data['check_number'])) {
            $normalized['check_number'] = $data['check_number'];
            $normalized['bank_name'] = $data['check_bank'] ?? null;
        }
        if (isset($data['bank_reference'])) {
            $normalized['bank_reference'] = $data['bank_reference'];
            $normalized['bank_name'] = $data['bank_name'] ?? null;
        }
        if (isset($data['reference_number'])) {
            $normalized['reference_number'] = $data['reference_number'];
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        $paymentType = $metadata['payment_method_type'] ?? 'manual';
        if (isset($metadata['check_number'])) {
            $display = "Check #{$metadata['check_number']}";
            if (isset($metadata['bank_name'])) {
                $display .= " ({$metadata['bank_name']})";
            }

            return $display;
        }
        if (isset($metadata['bank_reference'])) {
            $display = 'Bank Transfer';
            if (isset($metadata['bank_name'])) {
                $display .= " ({$metadata['bank_name']})";
            }
            $display .= " - Ref: {$metadata['bank_reference']}";

            return $display;
        }
        if ($paymentType === 'cash') {
            return 'Cash Payment';
        }
        if (isset($metadata['reference_number'])) {
            return ucwords(str_replace('_', ' ', $paymentType))." - Ref: {$metadata['reference_number']}";
        }

        return ucwords(str_replace('_', ' ', $paymentType));
    }
}
