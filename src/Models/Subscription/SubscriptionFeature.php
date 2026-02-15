<?php

namespace Coderstm\Models\Subscription;

use Carbon\Carbon;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionFeature extends Model
{
    use HasFactory, SerializeDate;

    protected $table = 'subscription_features';

    protected $fillable = ['subscription_id', 'slug', 'label', 'type', 'resetable', 'value', 'used', 'reset_at'];

    protected $casts = ['resetable' => 'boolean', 'reset_at' => 'datetime'];

    protected $appends = ['remaining'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(\Coderstm\Models\Subscription::class);
    }

    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    public function expired(): bool
    {
        if (is_null($this->reset_at)) {
            return false;
        }

        return Carbon::now()->gte($this->reset_at);
    }

    public function getRemainingAttribute(): int
    {
        return $this->value - $this->used;
    }

    public function canUse(): bool
    {
        if ($this->isBoolean()) {
            return $this->value === 1;
        }
        if ($this->value === -1) {
            return true;
        }

        return $this->remaining > 0;
    }

    public function recordUsage(int $uses = 1, bool $incremental = true): self
    {
        if ($this->isBoolean()) {
            return $this;
        }
        $this->used = $incremental ? $this->used + $uses : $uses;
        $this->save();

        return $this;
    }

    public function reduceUsage(int $uses = 1): self
    {
        $this->used = max($this->used - $uses, 0);
        $this->save();

        return $this;
    }

    public function resetUsage(): self
    {
        $this->used = 0;
        $this->reset_at = $this->subscription->plan->getResetDate(now());
        $this->save();

        return $this;
    }
}
