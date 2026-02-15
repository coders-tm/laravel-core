<?php

namespace Coderstm\Traits;

trait OrderStatus
{
    const STATUS_OPEN = 'open';

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_DECLINED = 'declined';

    const STATUS_DISPUTED = 'disputed';

    const STATUS_ARCHIVED = 'archived';

    const STATUS_PENDING_PAYMENT = 'pending_payment';

    const STATUS_PROCESSING = 'processing';

    const STATUS_SHIPPED = 'shipped';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_PAYMENT_PENDING = 'payment_pending';

    const STATUS_PAYMENT_FAILED = 'payment_failed';

    const STATUS_PAYMENT_SUCCESS = 'payment_success';

    const STATUS_PARTIALLY_PAID = 'partially_paid';

    const STATUS_PAID = 'paid';

    const STATUS_UNFULFILLED = 'unfulfilled';

    const STATUS_FULFILLED = 'fulfilled';

    const STATUS_PARTIALLY_FULFILLED = 'partially_fulfilled';

    const STATUS_AWAITING_PICKUP = 'awaiting_pickup';

    const STATUS_FULFILLMENT_CANCELLED = 'fulfillment_cancelled';

    const STATUS_FULFILLMENT_DELIVERED = 'fulfillment_delivered';

    const STATUS_FULFILLMENT_SHIPPED = 'fulfillment_shipped';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    const STATUS_RETURN_INPROGRESS = 'return_in_progress';

    const STATUS_RETURNED = 'returned';

    const STATUS_MANUAL_VERIFICATION_REQUIRED = 'manual_verification_required';

    public function markAsOpen()
    {
        $this->update(['status' => static::STATUS_PENDING_PAYMENT, 'payment_status' => static::STATUS_PAYMENT_PENDING]);

        return $this;
    }

    public function markAsPending()
    {
        $this->update(['status' => static::STATUS_PROCESSING]);

        return $this;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => static::STATUS_DELIVERED, 'fulfillment_status' => static::STATUS_FULFILLMENT_DELIVERED, 'delivered_at' => now()]);

        return $this;
    }

    public function markAsCancelled($reason = null)
    {
        $this->update(['status' => static::STATUS_CANCELLED, 'fulfillment_status' => static::STATUS_FULFILLMENT_CANCELLED, 'cancelled_at' => now()]);
        $reasonMessage = $this->getCancellationReason($reason);
        $this->logs()->create(['type' => 'canceled', 'message' => 'Order has been canceled. Reason: '.$reasonMessage]);

        return $this;
    }

    protected function getCancellationReason($reason)
    {
        if (empty($reason)) {
            return 'No reason provided';
        }
        $constantName = 'Coderstm\\Models\\Shop\\Order::REASON_'.strtoupper($reason);
        if (defined($constantName)) {
            return constant($constantName);
        }

        return ucfirst(str_replace('_', ' ', strtolower($reason)));
    }

    public function markAsPartiallyPaid()
    {
        $this->update(['payment_status' => static::STATUS_PARTIALLY_PAID]);

        return $this;
    }

    public function markAsRefunded()
    {
        $this->update(['payment_status' => static::STATUS_REFUNDED]);

        return $this;
    }

    public function markAsPartiallyRefunded()
    {
        $this->update(['payment_status' => static::STATUS_PARTIALLY_REFUNDED]);

        return $this;
    }

    public function syncCurrentStatus()
    {
        if ($this->refund_total == $this->paid_total) {
            $this->markAsRefunded();
        } elseif ($this->refund_total > 0) {
            $this->markAsPartiallyRefunded();
        } else {
            if (in_array($this->payment_status, [static::STATUS_REFUNDED, static::STATUS_PARTIALLY_REFUNDED])) {
                $this->update(['payment_status' => static::STATUS_PAID]);
            }
        }

        return $this;
    }
}
