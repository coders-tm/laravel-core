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
     * Flag to control automatic invoice generation on creation.
     *
     * @var bool
     */
    protected static $generateInvoiceOnCreation = true;

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
        'provider',
        'options',
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
     * Set the payment provider for the subscription.
     *
     * @param  string  $provider
     * @return $this
     */
    public function provider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set the status for the subscription.
     *
     * @param  string  $status
     * @return $this
     */
    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Create a new subscription without generating an invoice.
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        static::$generateInvoiceOnCreation = false;
        $model = static::query()->create($attributes);
        static::$generateInvoiceOnCreation = true;

        return $model;
    }

    /**
     * Create a new subscription and generate an invoice.
     *
     * @param array $attributes
     * @return static
     */
    public static function createAndInvoice(array $attributes = []): self
    {
        static::$generateInvoiceOnCreation = true;
        $model = static::query()->create($attributes);

        return $model;
    }

    /**
     * Save the subscription without generating an invoice.
     *
     * @param array $options
     * @return bool
     */
    public function saveWithoutInvoice(array $options = []): bool
    {
        static::$generateInvoiceOnCreation = false;
        $result = $this->save($options);
        static::$generateInvoiceOnCreation = true;

        return $result;
    }

    /**
     * Save the subscription and generate an invoice.
     *
     * @param array $options
     * @return bool
     */
    public function saveAndInvoice(array $options = []): bool
    {
        static::$generateInvoiceOnCreation = true;
        return $this->save($options);
    }

    /**
     * Check if the subscription is product-based (shop subscription).
     *
     * @return bool
     */
    public function isProductBased()
    {
        return $this->type === 'shop';
    }

    /**
     * Get the current price of the subscription based on intro pricing and periods.
     *
     * @return float
     */
    public function getCurrentPrice()
    {
        // If no plan is associated, return 0
        if (!$this->plan) {
            return 0;
        }

        // Return the regular plan price
        return $this->plan->price;
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

        static::created(function (self $model): void {
            if (static::$generateInvoiceOnCreation) {
                $model->generateInvoice(true);
            }
        });

        static::deleted(function (self $model): void {
            $model->usages()->delete();
        });
    }
}
