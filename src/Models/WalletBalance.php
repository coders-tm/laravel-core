<?php

namespace Coderstm\Models;

use Coderstm\Database\Factories\WalletBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class WalletBalance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance'];

    protected $casts = ['balance' => 'decimal:2'];

    protected static function newFactory()
    {
        return WalletBalanceFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('coderstm.models.user'));
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function credit(float $amount, string $source, ?string $description = null, $transactionable = null, array $metadata = []): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $source, $description, $transactionable, $metadata) {
            $balanceBefore = $this->balance;
            $this->increment('balance', $amount);
            $balanceAfter = $this->fresh()->balance;

            return $this->transactions()->create(['user_id' => $this->user_id, 'type' => 'credit', 'source' => $source, 'amount' => $amount, 'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter, 'description' => $description, 'transactionable_type' => $transactionable ? get_class($transactionable) : null, 'transactionable_id' => $transactionable?->id, 'metadata' => $metadata]);
        });
    }

    public function debit(float $amount, string $source, ?string $description = null, $transactionable = null, array $metadata = []): WalletTransaction
    {
        if ($amount > $this->balance) {
            throw new \Exception('Insufficient wallet balance. Available: '.format_amount($this->balance).', Required: '.format_amount($amount));
        }

        return DB::transaction(function () use ($amount, $source, $description, $transactionable, $metadata) {
            $balanceBefore = $this->balance;
            $this->decrement('balance', $amount);
            $balanceAfter = $this->fresh()->balance;

            return $this->transactions()->create(['user_id' => $this->user_id, 'type' => 'debit', 'source' => $source, 'amount' => $amount, 'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter, 'description' => $description, 'transactionable_type' => $transactionable ? get_class($transactionable) : null, 'transactionable_id' => $transactionable?->id, 'metadata' => $metadata]);
        });
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return format_amount($this->balance, config('app.currency', 'USD'));
    }
}
