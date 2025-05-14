<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Traits\Logable;
use Coderstm\Traits\HasFeature;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Coderstm\Database\Factories\SubscriptionFactory;
use Coderstm\Traits;

class Subscription extends Model implements SubscriptionStatus
{
    use Traits\Actionable;
    use HasFeature, Logable, SerializeDate, HasFactory;
    use Traits\Subscription\HasSubscriptionStatus;
    use Traits\Subscription\ManagesSubscriptionPlan;
    use Traits\Subscription\ManagesSubscriptionPeriod;
    use Traits\Subscription\ManagesSubscriptionCoupon;
    use Traits\Subscription\HandlesSubscriptionInvoices;
    use Traits\Subscription\HandlesSubscriptionNotifications;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'plan_id',
        'is_downgrade',
        'next_plan',
        'trial_ends_at',
        'ends_at',
        'cancels_at',
        'expires_at',
        'starts_at',
        'canceled_at',
    ];

    protected $with = [
        'plan.features',
        'usages',
    ];

    protected $dispatchesEvents = [
        'created' => \Coderstm\Events\SubscriptionCreated::class,
        'updated' => \Coderstm\Events\SubscriptionUpdated::class,
    ];

    protected $casts = [
        'is_downgrade' => 'boolean',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'expires_at' => 'datetime',
        'starts_at' => 'datetime',
        'canceled_at' => 'datetime',
        'options' => 'json',
    ];

    /**
     * Get the user foreign key.
     *
     * @return string
     */
    public function getUserForeignKey()
    {
        return (new Coderstm::$subscriptionUserModel)->getForeignKey();
    }

    /**
     * Get the user relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->owner();
    }

    /**
     * Get the owner relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$subscriptionUserModel, $this->getUserForeignKey());
    }

    /**
     * Scope a query to only include subscriptions with users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasUser($query)
    {
        return $query->whereNotNull($this->getUserForeignKey());
    }

    /**
     * Sync the subscription usages.
     *
     * @return void
     */
    public function syncUsages()
    {
        if ($this->latestInvoice?->wasRecentlyCreated) {
            $this->usages()->delete();
        } else {
            $this->syncOrResetUsages();
        }
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return SubscriptionFactory::new();
    }

    /**
     * Bootstrap the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (empty($model->status)) {
                $model->status = static::INCOMPLETE;
            }
        });

        static::created(function (self $model): void {
            $model->generateInvoice(true);
        });

        static::deleted(function (self $model): void {
            $model->usages()->delete();
        });
    }
}
