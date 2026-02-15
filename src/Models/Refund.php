<?php

namespace Coderstm\Models;

use Coderstm\Models\Shop\Order;
use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use Logable, SerializeDate;

    protected $fillable = ['amount', 'reason', 'payment_id', 'order_id', 'to_wallet', 'wallet_transaction_id', 'metadata'];

    protected $casts = ['to_wallet' => 'boolean', 'amount' => 'decimal:2', 'metadata' => 'array'];

    protected static function booted(): void
    {
        static::created(function (Refund $refund) {
            $refund->updateOrderRefundTotal();
        });
        static::updated(function (Refund $refund) {
            if ($refund->wasChanged('amount')) {
                $refund->updateOrderRefundTotal();
            }
        });
        static::deleted(function (Refund $refund) {
            $refund->updateOrderRefundTotal();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function updateOrderRefundTotal(): void
    {
        if ($this->order_id && $this->order) {
            $refundTotal = $this->order->refunds()->sum('amount');
            $this->order->updateQuietly(['refund_total' => $refundTotal]);
        }
    }
}
