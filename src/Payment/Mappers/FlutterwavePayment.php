<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class FlutterwavePayment extends AbstractPayment
{
    public static function fromResponse(array $response, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        // Map Flutterwave statuses to our payment statuses
        $status = match (strtolower($response['status'] ?? 'pending')) {
            'successful', 'completed' => Payment::STATUS_COMPLETED,
            'failed', 'cancelled' => Payment::STATUS_FAILED,
            'pending' => Payment::STATUS_PROCESSING,
            default => Payment::STATUS_FAILED,
        };

        $transactionId = $response['tx_ref'] ?? $response['id'] ?? uniqid('flw_');
        $statusText = $response['status'] ?? 'pending';
        $note = "Flutterwave Transaction: {$transactionId} (Status: {$statusText})";

        if (isset($response['processor_response'])) {
            $note .= " - {$response['processor_response']}";
        }

        $processedAt = null;
        if (isset($response['created_at'])) {
            $processedAt = new DateTime($response['created_at']);
        }

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency ?? config('app.currency', 'NGN'),
            status: $status,
            note: $note,
            processedAt: $processedAt
        );
    }

    /**
     * Get additional Flutterwave-specific data
     */
    public function getFlutterwaveReference(): string
    {
        return $this->getTransactionId();
    }

    /**
     * Check if this is a successful Flutterwave payment
     */
    public function isSuccessful(): bool
    {
        return $this->getStatus() === Payment::STATUS_COMPLETED;
    }
}
