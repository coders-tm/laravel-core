<?php

namespace Coderstm\Traits;

use Coderstm\Models\WalletBalance;
use Coderstm\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasWallet
{
    public function walletBalance(): HasOne
    {
        return $this->hasOne(WalletBalance::class, 'user_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id');
    }

    public function getOrCreateWallet(): WalletBalance
    {
        return $this->walletBalance()->firstOrCreate(['user_id' => $this->id], ['balance' => 0.0]);
    }

    public function getWalletBalance(): float
    {
        return (float) $this->getOrCreateWallet()->balance;
    }

    public function creditWallet(float $amount, string $source, ?string $description = null, $transactionable = null, array $metadata = []): WalletTransaction
    {
        $wallet = $this->getOrCreateWallet();

        return $wallet->credit($amount, $source, $description, $transactionable, $metadata);
    }

    public function debitWallet(float $amount, string $source, ?string $description = null, $transactionable = null, array $metadata = []): WalletTransaction
    {
        $wallet = $this->getOrCreateWallet();

        return $wallet->debit($amount, $source, $description, $transactionable, $metadata);
    }

    public function hasWalletBalance(float $amount): bool
    {
        return $this->getWalletBalance() >= $amount;
    }

    public function getFormattedWalletBalanceAttribute(): string
    {
        $wallet = $this->getOrCreateWallet();

        return format_amount($wallet->balance, config('app.currency', 'USD'));
    }
}
