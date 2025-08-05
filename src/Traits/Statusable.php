<?php

namespace Coderstm\Traits;

trait OrderStatus
{
    // General order status constants
    const STATUS_OPEN = 'open';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DECLINED = 'declined';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_ARCHIVED = 'archived';

    // Additional order status constants
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';

    // Payment status constants (old style)
    const STATUS_PAYMENT_PENDING = 'payment_pending';
    const STATUS_PAYMENT_FAILED = 'payment_failed';
    const STATUS_PAYMENT_SUCCESS = 'payment_success';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';

    // Fulfillment status constants
    const STATUS_UNFULFILLED = 'unfulfilled';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_PARTIALLY_FULFILLED = 'partially_fulfilled';
    const STATUS_AWAITING_PICKUP = 'awaiting_pickup';

    // Additional fulfillment status constants
    const STATUS_FULFILLMENT_CANCELLED = 'fulfillment_cancelled';
    const STATUS_FULFILLMENT_DELIVERED = 'fulfillment_delivered';
    const STATUS_FULFILLMENT_SHIPPED = 'fulfillment_shipped';

    // Refund status constants
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    // Return status constants
    const STATUS_RETURN_INPROGRESS = 'return_in_progress';
    const STATUS_RETURNED = 'returned';

    // Other status constants
    const STATUS_MANUAL_VERIFICATION_REQUIRED = 'manual_verification_required';

    /**
     * Mark order as open
     */
    public function markAsOpen()
    {
        $this->update([
            'status' => static::STATUS_PENDING_PAYMENT,
            'payment_status' => static::STATUS_PAYMENT_PENDING,
        ]);
        return $this;
    }

    /**
     * Mark order as pending
     */
    public function markAsPending()
    {
        $this->update([
            'status' => static::STATUS_PROCESSING,
        ]);
        return $this;
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => static::STATUS_DELIVERED,
            'fulfillment_status' => static::STATUS_FULFILLMENT_DELIVERED,
            'delivered_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark order as cancelled
     */
    public function markAsCancelled($reason = null)
    {
        $this->update([
            'status' => static::STATUS_CANCELLED,
            'fulfillment_status' => static::STATUS_FULFILLMENT_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $reasonMessage = $this->getCancellationReason($reason);

        $this->logs()->create([
            'type' => "canceled",
            'message' => "Order has been canceled. Reason: " . $reasonMessage,
        ]);

        return $this;
    }

    /**
     * Get cancellation reason message with fallback
     */
    protected function getCancellationReason($reason)
    {
        if (empty($reason)) {
            return 'No reason provided';
        }

        // Try to get the constant value
        $constantName = "Coderstm\Models\Shop\Order::REASON_" . strtoupper($reason);

        if (defined($constantName)) {
            return constant($constantName);
        }

        // Fallback to the provided reason (format it nicely)
        return ucfirst(str_replace('_', ' ', strtolower($reason)));
    }

    /**
     * Mark order as partially paid
     */
    public function markAsPartiallyPaid()
    {
        $this->update([
            'payment_status' => static::STATUS_PARTIALLY_PAID,
        ]);
        return $this;
    }

    /**
     * Mark order as refunded
     */
    public function markAsRefunded()
    {
        $this->update([
            'payment_status' => static::STATUS_REFUNDED,
        ]);
        return $this;
    }

    /**
     * Mark order as partially refunded
     */
    public function markAsPartiallyRefunded()
    {
        $this->update([
            'payment_status' => static::STATUS_PARTIALLY_REFUNDED,
        ]);
        return $this;
    }

    /**
     * Sync current status based on payment totals
     */

    public function syncCurrentStatus()
    {
        if ($this->refund_total == $this->paid_total) {
            $this->markAsRefunded();
        } else if ($this->refund_total > 0) {
            $this->markAsPartiallyRefunded();
        } else {
            // Remove refund status if no refunds
            if (in_array($this->payment_status, [static::STATUS_REFUNDED, static::STATUS_PARTIALLY_REFUNDED])) {
                $this->update(['payment_status' => static::STATUS_PAID]);
            }
        }
        return $this;
    }
}
