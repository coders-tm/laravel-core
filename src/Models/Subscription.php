<?php

namespace Coderstm\Models;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Contracts\ManagesSubscriptions;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Database\Factories\SubscriptionFactory;
use Coderstm\Events\SubscriptionCreated;
use Coderstm\Events\SubscriptionUpdated;
use Coderstm\Exceptions\SubscriptionUpdateFailure;
use Coderstm\Jobs\Subscription\SendRenewNotificationJob;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Services\Period;
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
    use Traits\Subscription\ForwardsSubscriptionActions;
    use Traits\Subscription\ManagesInvoices;

    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'plan_id',
        'coupon_id',
        'is_downgrade',
        'next_plan',
        'trial_ends_at',
        'expires_at',
        'ends_at',
        'starts_at',
        'canceled_at',
        'frozen_at',
        'release_at',
        'provider',
        'metadata',
        'billing_interval',
        'billing_interval_count',
        'total_cycles',
        'current_cycle',
        'is_free_forever',
        'credit_resets_at',
    ];

    protected $with = [
        'features',
    ];

    protected $dispatchesEvents = [
        'created' => SubscriptionCreated::class,
        'updated' => SubscriptionUpdated::class,
    ];

    protected $casts = [
        'is_downgrade' => 'boolean',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
        'ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'canceled_at' => 'datetime',
        'frozen_at' => 'datetime',
        'release_at' => 'datetime',
        'metadata' => 'json',
        'billing_interval_count' => 'integer',
        'total_cycles' => 'integer',
        'current_cycle' => 'integer',
        'is_free_forever' => 'boolean',
        'credit_resets_at' => 'datetime',
    ];

    protected $hasCustomDates = false;

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

    /*
     * --------------------------------------------------------------------------
     * Plan Methods
     * --------------------------------------------------------------------------
     */

    public function hasPlan($plan)
    {
        return $this->plan_id === $plan;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$planModel);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$planModel, 'next_plan');
    }

    public function hasNexPlan()
    {
        return ! is_null($this->next_plan);
    }

    public function assertRenewable()
    {
        if ($this->ended()) {
            throw new \LogicException('Unable to renew canceled ended subscription.');
        }

        if ($this->onGracePeriod()) {
            throw new \LogicException('Unable to renew subscription that is not within grace period.');
        }
    }

    public function assertChargeable()
    {
        if ($this->expired() || $this->hasIncompletePayment()) {
            return;
        }

        throw new \LogicException('Unable to charge subscription that is not expired.');
    }

    /*
     * --------------------------------------------------------------------------
     * Period Methods
     * --------------------------------------------------------------------------
     */

    protected function anchorActivationFromInvoice(): bool
    {
        return (bool) config('coderstm.subscription.anchor_from_invoice', false) && $this->expires_at && $this->expires_at->isFuture();
    }

    public function setPeriod(string $interval = '', ?int $count = null, ?Carbon $dateFrom = null, bool $force = false): self
    {
        if (! $force && $this->hasCustomDates) {
            return $this;
        }

        if ($this->anchorActivationFromInvoice()) {
            $dateFrom = $this->starts_at?->copy() ?? $dateFrom;
        }

        if (empty($interval)) {
            $interval = $this->plan->interval->value;
        }

        if (empty($count)) {
            $count = $this->plan->interval_count;
        }

        $period = new Period($interval, $count, $dateFrom ?? Carbon::now());

        $this->fill([
            'starts_at' => $period->getStartDate(),
            'expires_at' => $period->getEndDate(),
            'billing_interval' => $this->plan->interval->value,
            'billing_interval_count' => $this->plan->interval_count,
        ]);

        if ($this->plan->isContract() && is_null($this->total_cycles)) {
            $this->total_cycles = $this->plan->contract_cycles;
            $this->current_cycle = 0;
        }

        return $this;
    }

    public function contractCycles(?int $cycles): self
    {
        $this->total_cycles = $cycles;
        $this->current_cycle = 0;

        return $this;
    }

    public function contractComplete(): bool
    {
        if (! $this->total_cycles) {
            return false;
        }

        return $this->current_cycle >= $this->total_cycles;
    }

    protected function setPeriodFromDate(Carbon $dateFrom): self
    {
        return $this->setPeriod('', null, $dateFrom);
    }

    protected function dateFrom(): Carbon
    {
        return $this->starts_at ?? $this->created_at;
    }

    public function getBillingInterval(): string
    {
        if ($this->billing_interval) {
            return is_string($this->billing_interval) ? $this->billing_interval : $this->billing_interval->value;
        }

        return $this->plan->interval->value;
    }

    public function getBillingIntervalCount(): int
    {
        return $this->billing_interval_count ?? $this->plan->interval_count;
    }

    public function getPrice(?string $billingInterval = null): float
    {
        $plan = $this->plan;
        $interval = $billingInterval ?? $this->getBillingInterval();

        return $interval === 'year'
            ? (float) ($plan?->yearly_fee ?? $plan?->price)
            : (float) ($plan?->price ?? 0);
    }

    public function isContract(): bool
    {
        return $this->plan && $this->plan->isContract();
    }

    public function setStartsAt($date): self
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        } elseif ($date instanceof \DateTimeInterface && ! $date instanceof Carbon) {
            $date = Carbon::instance($date);
        }

        $this->starts_at = $date;
        $this->hasCustomDates = true;

        return $this;
    }

    public function setExpiresAt($date): self
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        } elseif ($date instanceof \DateTimeInterface && ! $date instanceof Carbon) {
            $date = Carbon::instance($date);
        }

        $this->expires_at = $date;
        $this->hasCustomDates = true;

        return $this;
    }

    public function advanceCreditResetsAt($date = null): self
    {
        if ($this->plan) {
            $period = new Period(
                $this->plan->interval->value,
                $this->plan->interval_count,
                $date ?? $this->credit_resets_at ?? $this->expires_at
            );
            $this->credit_resets_at = $period->getEndDate();
        }

        return $this;
    }

    /*
     * --------------------------------------------------------------------------
     * Coupon Methods
     * --------------------------------------------------------------------------
     */

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$couponModel);
    }

    public function withCoupon($coupon): self
    {
        $couponModel = Coderstm::$couponModel;
        if ($coupon = $couponModel::findByCode($coupon)) {
            $this->coupon()->associate($coupon);
        }

        return $this;
    }

    public function canApplyCoupon($coupon = null)
    {
        $coupon = $coupon ?? $this->coupon;
        $foreignKey = $this->getUserForeignKey();
        $userId = $this->{$foreignKey};

        if ($coupon && $coupon->canApplyToPlan($this->plan)) {
            if ($coupon->duration->value === 'once') {
                if ($coupon->redeems()->where($foreignKey, $userId)->exists()) {
                    return null;
                }
            }

            if ($coupon->duration->value === 'repeating') {
                if ($coupon->redeems()->where($foreignKey, $userId)->count() >= $coupon->duration_in_months) {
                    return null;
                }
            }

            return $coupon;
        }

        return null;
    }

    protected function discount()
    {
        if ($coupon = $this->canApplyCoupon()) {
            $discountType = match ($coupon->discount_type) {
                'percentage' => DiscountLine::TYPE_PERCENTAGE,
                'fixed' => DiscountLine::TYPE_FIXED_AMOUNT,
                'override' => DiscountLine::TYPE_PRICE_OVERRIDE,
                default => DiscountLine::TYPE_PERCENTAGE
            };

            return [
                'type' => $discountType,
                'value' => $coupon->value,
                'description' => $coupon->name,
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->promotion_code,
                'auto_applied' => false,
            ];
        }

        return null;
    }

    /*
     * --------------------------------------------------------------------------
     * Freeze Methods
     * --------------------------------------------------------------------------
     */

    public function onFreeze(): bool
    {
        return ! is_null($this->frozen_at) &&
            $this->status === SubscriptionStatus::PAUSED &&
            (is_null($this->release_at) || $this->release_at->isFuture());
    }

    public function canFreeze(int $days = 0): bool
    {
        if (! $this->plan->allowsFreeze()) {
            return false;
        }

        if ($this->onFreeze()) {
            return false;
        }

        if ($this->canceled() || $this->expired()) {
            return false;
        }

        return true;
    }

    public function scopeFrozen($query)
    {
        return $query->where('status', SubscriptionStatus::PAUSED)
            ->whereNotNull('frozen_at');
    }

    public function scopeDueForUnfreeze($query)
    {
        return $query->frozen()
            ->whereNotNull('release_at')
            ->where('release_at', '<=', now());
    }

    /*
     * --------------------------------------------------------------------------
     * Notification Methods
     * --------------------------------------------------------------------------
     */

    public function getShortCodes(): array
    {
        return [
            'user' => $this->user?->toArray(),
            'plan' => [
                'label' => $this->plan?->label,
                'price' => $this->plan?->formatPrice(),
            ],
            'billing_page' => user_route('/billing'),
            'renew_url' => user_route('/billing?renew=1'),
            'subscription_status' => is_string($this->status) ? $this->status : ($this->status->value ?? ''),
            'billing_cycle' => $this->formatBillingInterval(),
            'next_billing_date' => $this->expires_at ? $this->expires_at->format('d M, Y') : '',
            'ends_at' => $this->ends_at ? $this->ends_at->format('d M, Y') : '',
            'starts_at' => $this->starts_at ? $this->starts_at->format('d M, Y') : '',
            'expires_at' => $this->expires_at ? $this->expires_at->format('d M, Y') : '',
            'upcoming_invoice' => $this->upcomingInvoice(),
        ];
    }

    public function sendRenewNotification(): void
    {
        if (! $this->user) {
            return;
        }

        if ($this->expired()) {
            SendRenewNotificationJob::dispatch($this)->afterResponse();
        }
    }

    public function renderNotification($type, $additionalData = []): ?Notification
    {
        $template = Notification::default($type);

        $data = array_merge($this->getShortCodes(), $additionalData);

        $rendered = $template->render($data);

        return $template->fill([
            'subject' => $rendered['subject'],
            'content' => $rendered['content'],
            'text' => $rendered['text'],
        ]);
    }

    public function renderPushNotification($type, $additionalData = [])
    {
        $template = $this->renderNotification($type, $additionalData);

        return optional((object) [
            'subject' => $template->subject,
            'content' => $template->text,
            'whatsappContent' => $template->text
                ? "{$template->subject}\n{$template->text}"
                : $template->subject,
            'data' => [
                'route' => user_route('/billing'),
            ],
        ]);
    }

    /*
     * --------------------------------------------------------------------------
     * Status Methods
     * --------------------------------------------------------------------------
     */

    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->canceledOnGracePeriod() || $this->onGracePeriod();
    }

    public function pending(): bool
    {
        return $this->status === SubscriptionStatus::PENDING;
    }

    public function scopePending($query)
    {
        $query->where('status', SubscriptionStatus::PENDING);
    }

    public function incomplete(): bool
    {
        return $this->status === SubscriptionStatus::INCOMPLETE;
    }

    public function scopeIncomplete($query)
    {
        $query->where('status', SubscriptionStatus::INCOMPLETE);
    }

    public function expired(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED;
    }

    public function scopeExpired($query)
    {
        $query->where('status', SubscriptionStatus::EXPIRED);
    }

    public function active(): bool
    {
        return ! $this->ended() && $this->status === SubscriptionStatus::ACTIVE;
    }

    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->whereNull('canceled_at')
                ->orWhere(function ($query) {
                    $query->canceledOnGracePeriod();
                });
        })->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING]);
    }

    public function scopeFree($query)
    {
        $query->whereHas('plan', function ($query) {
            $query->where('price', 0);
        });
    }

    public function recurring(): bool
    {
        return ! $this->onTrial() && ! $this->canceled();
    }

    public function scopeRecurring($query)
    {
        $query->notOnTrial()->notCanceled();
    }

    public function canceled(): bool
    {
        return ! is_null($this->canceled_at);
    }

    public function scopeCanceled($query)
    {
        $query->whereNotNull('canceled_at');
    }

    public function scopeNotCanceled($query)
    {
        $query->whereNull('canceled_at');
    }

    public function ended()
    {
        return $this->canceled() && ! $this->canceledOnGracePeriod();
    }

    public function scopeEnded($query)
    {
        $query->canceled()->canceledNotOnGracePeriod();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasDowngrade(): bool
    {
        return $this->is_downgrade && $this->next_plan;
    }

    public function scopeOnTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    public function hasExpiredTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function scopeExpiredTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

    public function scopeNotOnTrial($query)
    {
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', Carbon::now());
    }

    public function canceledOnGracePeriod(): bool
    {
        return $this->canceled_at && $this->expires_at && $this->expires_at->isFuture();
    }

    public function scopeCanceledOnGracePeriod($query)
    {
        $query->whereNotNull('canceled_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', Carbon::now());
    }

    public function scopeCanceledNotOnGracePeriod($query)
    {
        $query->whereNotNull('canceled_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now());
    }

    public function onGracePeriod(): bool
    {
        if ($this->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        return $this->ends_at?->isFuture() ?? false;
    }

    public function notOnGracePeriod(): bool
    {
        return ! $this->onGracePeriod();
    }

    public function scopeOnGracePeriod($query)
    {
        $query->where('status', SubscriptionStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', Carbon::now());
    }

    public function scopeNotOnGracePeriod($query)
    {
        $query->where(function ($q) {
            $q->where('status', '<>', SubscriptionStatus::ACTIVE)
                ->orWhereNull('ends_at')
                ->orWhere('ends_at', '<=', Carbon::now());
        });
    }

    public function hasIncompletePayment(): bool
    {
        return $this->expired() || $this->incomplete() || $this->pending();
    }

    public function guardAgainstIncomplete()
    {
        if ($this->incomplete()) {
            throw SubscriptionUpdateFailure::incompleteSubscription($this);
        }
    }

    public function toResponse(array $extends = []): array
    {
        $status = [
            'id' => $this->id,
            'status' => $this->status,
            'active' => $this->active(),
            'canceled' => $this->canceled(),
            'ended' => $this->ended(),
            'expired' => $this->expired(),
            'downgrade' => $this->hasDowngrade(),
            'on_grace_period' => $this->onGracePeriod(),
            'canceled_on_grace_period' => $this->canceledOnGracePeriod(),
            'has_incomplete_payment' => $this->hasIncompletePayment(),
            'has_due' => $this->onGracePeriod() || $this->expired() || $this->hasIncompletePayment(),
            'on_trial' => $this->onTrial(),
            'is_valid' => $this->valid() ?? false,
            'type' => $this->type,
            'is_downgrade' => $this->is_downgrade,
            'next_plan' => $this->next_plan,
            'trial_ends_at' => $this->serializeDate($this->trial_ends_at),
            'expires_at' => $this->serializeDate($this->expires_at),
            'ends_at' => $this->serializeDate($this->ends_at),
            'starts_at' => $this->serializeDate($this->starts_at),
            'canceled_at' => $this->serializeDate($this->canceled_at),
            'frozen_at' => $this->serializeDate($this->frozen_at),
            'release_at' => $this->serializeDate($this->release_at),
            'provider' => $this->provider,
            'metadata' => $this->metadata ?? [],
            'billing_interval' => $this->billing_interval,
            'billing_interval_count' => $this->billing_interval_count,
            'total_cycles' => $this->total_cycles,
            'current_cycle' => $this->current_cycle,
            'credit_resets_at' => $this->serializeDate($this->credit_resets_at),
            'created_at' => $this->serializeDate($this->created_at),
            'updated_at' => $this->serializeDate($this->updated_at),
            'invoice' => null,
        ];

        try {
            $upcomingInvoice = $this->upcomingInvoice();
        } catch (\Throwable $e) {
            $upcomingInvoice = null;
        }

        if ($this->onGracePeriod() || $this->expired() || $this->hasIncompletePayment()) {
            $invoice = $this->latestInvoice ?? $upcomingInvoice;
            $amount = $invoice?->total();
            $status['invoice'] = [
                'amount' => $amount,
                'key' => $invoice?->key,
            ];
        } elseif ($upcomingInvoice) {
            $status['invoice'] = [
                'amount' => $upcomingInvoice->total(),
                'date' => $this->expires_at->format('d M, Y'),
            ];
        }

        if (in_array('plan', $extends)) {
            $status['plan'] = $this->plan;
        }

        if (in_array('user', $extends)) {
            $status['user'] = $this->user;
        }

        if (in_array('next_plan', $extends) && $this->hasDowngrade()) {
            $status['next_plan'] = $this->nextPlan;
        }

        if (in_array('usages', $extends)) {
            $status['usages'] = $this->usagesToArray();
        }

        return $status;
    }

    public function getCreditResetsAtAttribute($value)
    {
        return $value ? $this->asDateTime($value) : $this->expires_at;
    }
}
