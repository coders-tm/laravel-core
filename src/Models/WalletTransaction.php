<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['wallet_balance_id', 'user_id', 'type', 'source', 'amount', 'balance_before', 'balance_after', 'description', 'transactionable_type', 'transactionable_id', 'metadata'];

    protected $casts = ['amount' => 'decimal:2', 'balance_before' => 'decimal:2', 'balance_after' => 'decimal:2', 'metadata' => 'array'];

    protected static function newFactory()
    {
        return WalletTransactionFactory::new();
    }

    public function walletBalance(): BelongsTo
    {
        return $this->belongsTo(WalletBalance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$userModel);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->type === 'credit' ? '+' : '-';

        return $prefix.format_amount($this->amount, config('app.currency', 'USD'));
    }
}
