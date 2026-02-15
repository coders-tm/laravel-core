<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Contracts\ManagesSubscriptions;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Database\Factories\SubscriptionFactory;
use Coderstm\Traits;
use Coderstm\Traits\HasFeature;
use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model implements ManagesSubscriptions, SubscriptionStatus
{
    use HasFactory, HasFeature, Logable, SerializeDate;
    use Traits\Actionable;
    use Traits\Subscription\ManagesSubscription;

    protected $table = 'subscriptions';

    protected $fillable = ['user_id', 'type', 'status', 'plan_id', 'coupon_id', 'is_downgrade', 'next_plan', 'trial_ends_at', 'expires_at', 'ends_at', 'starts_at', 'canceled_at', 'frozen_at', 'release_at', 'provider', 'metadata', 'billing_interval', 'billing_interval_count', 'total_cycles', 'current_cycle'];

    protected $with = ['features'];

    protected $dispatchesEvents = ['created' => \Coderstm\Events\SubscriptionCreated::class, 'updated' => \Coderstm\Events\SubscriptionUpdated::class];

    protected $casts = ['is_downgrade' => 'boolean', 'trial_ends_at' => 'datetime', 'expires_at' => 'datetime', 'ends_at' => 'datetime', 'starts_at' => 'datetime', 'canceled_at' => 'datetime', 'frozen_at' => 'datetime', 'release_at' => 'datetime', 'metadata' => 'json', 'billing_interval_count' => 'integer', 'total_cycles' => 'integer', 'current_cycle' => 'integer'];

    public function getUserForeignKey()
    {
        return (new Coderstm::$subscriptionUserModel)->getForeignKey();
    }

    public function user(): BelongsTo
    {
        return $this->owner();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$subscriptionUserModel, $this->getUserForeignKey());
    }

    public function scopeHasUser($query)
    {
        return $query->whereNotNull($this->getUserForeignKey());
    }

    public function syncUsages()
    {
        if ($this->wasRecentlyCreated) {
            $this->syncFeaturesFromPlan();
        } else {
            $this->syncOrResetUsages();
        }
    }

    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function saveWithoutInvoice(array $options = []): self
    {
        $this->save($options);

        return $this;
    }

    public function saveAndInvoice(array $options = [], bool $force = false): self
    {
        $this->save($options);
        if (! $this->onTrial() || $force) {
            $this->generateInvoice(true, $force);
        }

        return $this;
    }

    public function isContractBased(): bool
    {
        return ! is_null($this->total_cycles) && $this->total_cycles > 0;
    }

    protected static function newFactory()
    {
        return SubscriptionFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::created(function (self $model): void {
            $model->syncFeaturesFromPlan();
        });
        static::deleted(function (self $model): void {
            $model->features()->delete();
        });
    }

    public function formatBillingInterval(): string
    {
        if (! $this->billing_interval) {
            return '';
        }
        $interval = is_string($this->billing_interval) ? $this->billing_interval : $this->billing_interval->value;
        $count = $this->billing_interval_count ?? 1;
        if ($count > 1) {
            return "{$count} {$interval}s";
        }

        return $interval;
    }
}
