<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Coderstm\Models\Shop\Order;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Payment as CashierPayment;

class Payment extends Model
{
    use Core;

    protected $fillable = [
        'paymentable_type',
        'paymentable_id',
        'payment_method_id',
        'transaction_id',
        'amount',
        'capturable',
        'status',
        'note',
        'metadata',
        // Additional shop-specific fields
        'currency',
        'fees',
        'net_amount',
        'processed_at',
        'refund_amount',
    ];

    protected $hidden = [
        'paymentable_type',
        'paymentable_id',
    ];

    protected $casts = [
        'capturable' => 'boolean',
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $with = [
        'paymentMethod',
    ];

    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Scope to get successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    /**
     * Calculate refundable amount
     */
    public function getRefundableAmountAttribute(): float
    {
        if (!$this->isSuccessful()) {
            return 0;
        }

        return $this->amount - ($this->refund_amount ?? 0);
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed($reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund($amount = null): bool
    {
        $refundAmount = $amount ?? $this->amount;
        $totalRefunded = ($this->refund_amount ?? 0) + $refundAmount;

        $status = $totalRefunded >= $this->amount
            ? self::STATUS_REFUNDED
            : self::STATUS_PARTIALLY_REFUNDED;

        return $this->update([
            'status' => $status,
            'refund_amount' => $totalRefunded,
            'refunded_at' => now(),
        ]);
    }

    /**
     * Create payment for order
     */
    public static function createForOrder(Order $order, array $attributes = [])
    {
        return static::updateOrCreate([
            'paymentable_type' => Order::class,
            'paymentable_id' => $order->id,
            'payment_method_id' => $attributes['payment_method_id'] ?? null,
            'transaction_id' => $attributes['transaction_id'] ?? null,
        ], $attributes);
    }

    /**
     * @deprecated Use createForOrder() instead.
     */
    public static function createFromRazorpay(array $options)
    {
        // Deprecated: Use createForOrder() instead.
        return static::create($options + [
            'payment_method_id' => PaymentMethod::razorpayId(),
        ]);
    }

    /**
     * @deprecated Use createForOrder() instead.
     */
    public static function createFromPaypal(array $options)
    {
        // Deprecated: Use createForOrder() instead.
        return static::create($options + [
            'payment_method_id' => PaymentMethod::paypalId(),
        ]);
    }

    /**
     * @deprecated Use createForOrder() instead.
     */
    public static function createFromStripe(array $options)
    {
        // Deprecated: Use createForOrder() instead.
        return static::create($options + [
            'payment_method_id' => PaymentMethod::stripeId(),
        ]);
    }

    /**
     * @deprecated Use createForOrder() instead.
     */
    public static function createFromStripePayment(CashierPayment $payment, array $options = [])
    {
        // Deprecated: Use createForOrder() instead.
        return static::createUsingStripe($options + [
            'transaction_id' => $payment->id,
            'amount' => $payment->amount / 100,
            'status' => $payment->status,
        ]);
    }
}
