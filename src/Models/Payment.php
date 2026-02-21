<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use Core, HasFactory;

    protected $fillable = ['uuid', 'paymentable_type', 'paymentable_id', 'payment_method_id', 'transaction_id', 'amount', 'capturable', 'status', 'note', 'metadata', 'fees', 'net_amount', 'processed_at', 'refund_amount'];

    protected $hidden = ['paymentable_type', 'paymentable_id'];

    protected $casts = ['capturable' => 'boolean', 'amount' => 'decimal:2', 'fees' => 'decimal:2', 'net_amount' => 'decimal:2', 'refund_amount' => 'decimal:2', 'processed_at' => 'datetime', 'metadata' => 'array'];

    protected $with = ['paymentMethod'];

    protected static function newFactory()
    {
        return \Coderstm\Database\Factories\PaymentFactory::new();
    }

    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }
        });
        static::created(function (Payment $payment) {
            $payment->updateOrderPaidTotal();
        });
        static::updated(function (Payment $payment) {
            if ($payment->wasChanged(['amount', 'status'])) {
                $payment->updateOrderPaidTotal();
            }
        });
        static::deleted(function (Payment $payment) {
            $payment->updateOrderPaidTotal();
        });
    }

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function getRefundableAmountAttribute(): float
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return 0;
        }

        return $this->amount;
    }

    public function markAsCompleted(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED, 'processed_at' => now()]);
    }

    public function markAsFailed($reason = null): bool
    {
        return $this->update(['status' => self::STATUS_FAILED, 'failure_reason' => $reason]);
    }

    public function processRefund($reason = null): bool
    {
        $refundAmount = $this->amount;
        $status = self::STATUS_REFUNDED;
        $updated = $this->update(['status' => $status, 'refund_amount' => $refundAmount, 'refunded_at' => now()]);
        if ($updated && $this->paymentable_type === Coderstm::$orderModel) {
            $order = $this->paymentable;
            event(new \Coderstm\Events\Shop\OrderRefunded($order, $this, $refundAmount, $reason));
        }

        return $updated;
    }

    public function updateOrderPaidTotal(): void
    {
        if ($this->paymentable_type === Coderstm::$orderModel && $this->paymentable) {
            $paidTotal = $this->paymentable->payments()->sum('amount');
            $this->paymentable->updateQuietly(['paid_total' => $paidTotal]);
        }
    }

    public static function createForOrder($order, array $attributes = [])
    {
        return static::updateOrCreate(['paymentable_type' => Coderstm::$orderModel, 'paymentable_id' => $order->id, 'payment_method_id' => $attributes['payment_method_id'] ?? null, 'transaction_id' => $attributes['transaction_id'] ?? null], $attributes);
    }

    protected function gatewayPaymentMethod(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->metadata['payment_method'] ?? $this->paymentMethod?->name ?? 'Unknown';
        });
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'transaction_id' => $this->transaction_id, 'payment_method' => ['name' => $this->gateway_payment_method, 'provider' => $this->paymentMethod?->provider ?? 'Unknown', 'provider_name' => $this->paymentMethod?->name ?? 'Unknown', 'type' => $this->metadata['payment_method_type'] ?? null, 'card_brand' => $this->metadata['card_brand'] ?? null, 'last_four' => $this->metadata['last_four'] ?? null, 'bank_name' => $this->metadata['bank_name'] ?? null, 'wallet_type' => $this->metadata['wallet_type'] ?? null, 'upi_id' => $this->metadata['upi_id'] ?? null], 'amount' => format_amount($this->amount ?? 0), 'gateway_amount' => isset($this->metadata['gateway_amount']) ? format_amount($this->metadata['gateway_amount'], $this->metadata['gateway_currency']) : null, 'fees' => format_amount($this->fees ?? 0), 'net_amount' => format_amount($this->net_amount ?? 0), 'refund_amount' => format_amount($this->refund_amount ?? 0), 'refundable_amount' => format_amount($this->refundable_amount ?? 0), 'raw_amount' => $this->amount ?? 0, 'status' => ucfirst($this->status ?? 'pending'), 'is_successful' => $this->isSuccessful(), 'is_pending' => $this->isPending(), 'is_failed' => $this->isFailed(), 'is_refunded' => $this->isRefunded(), 'created_at' => optional($this->created_at)->format('M d, Y h:i A'), 'processed_at' => optional($this->processed_at)->format('M d, Y h:i A'), 'date' => optional($this->created_at)->format('M d, Y'), 'currency' => $this->currency ?? config('cashier.currency', 'USD'), 'note' => $this->note, 'capturable' => (bool) $this->capturable, 'can_refund' => $this->refundable_amount > 0];
    }
}
