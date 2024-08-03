<?php

namespace Coderstm\Models;

use LogicException;
use Coderstm\Coderstm;
use DateTimeInterface;
use Coderstm\Models\Log;
use Carbon\CarbonInterface;
use Coderstm\Models\Coupon;
use Coderstm\Traits\Logable;
use Coderstm\Services\Period;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Resource;
use Coderstm\Traits\HasFeature;
use Coderstm\Models\Notification;
use Coderstm\Traits\SerializeDate;
use Illuminate\Support\Facades\DB;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Jobs\SendPushNotification;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Subscription\Invoice;
use Coderstm\Events\SubscriptionProcessed;
use Coderstm\Repository\InvoiceRepository;
use Coderstm\Jobs\SendWhatsappNotification;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Coderstm\Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFeature, Logable, SerializeDate;

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_PAUSED = 'paused';
    const STATUS_TRIALING = 'trialing';
    const STATUS_UNPAID = 'unpaid';

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
        'created' => SubscriptionProcessed::class,
        'updated' => SubscriptionProcessed::class,
    ];

    protected $casts = [
        'is_downgrade' => 'boolean',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'expires_at' => 'datetime',
        'starts_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->owner();
    }

    public function owner(): BelongsTo
    {
        $model = Coderstm::$userModel;

        return $this->belongsTo($model, (new $model)->getForeignKey());
    }

    /**
     * Determine if the subscription has a specific plan.
     *
     * @param  string  $plan
     * @return bool
     */
    public function hasPlan($plan)
    {
        return $this->plan_id === $plan;
    }

    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is incomplete.
     *
     * @return bool
     */
    public function incomplete()
    {
        return $this->status === static::STATUS_INCOMPLETE;
    }

    /**
     * Filter query by incomplete.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeIncomplete($query)
    {
        $query->where('status', static::STATUS_INCOMPLETE);
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->status === static::STATUS_PAST_DUE;
    }

    /**
     * Filter query by past due.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePastDue($query)
    {
        $query->where('status', static::STATUS_PAST_DUE);
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return !$this->ended() && !in_array($this->status, [
            static::STATUS_INCOMPLETE,
            static::STATUS_INCOMPLETE_EXPIRED,
            static::STATUS_PAST_DUE,
            static::STATUS_UNPAID
        ]);
    }

    /**
     * Filter query by active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->whereNull('ends_at')
                ->orWhere(function ($query) {
                    $query->onGracePeriod();
                });
        })->whereNotIn('status', [
            static::STATUS_INCOMPLETE,
            static::STATUS_INCOMPLETE_EXPIRED,
            static::STATUS_PAST_DUE,
            static::STATUS_UNPAID
        ]);
    }

    /**
     * Determine if the subscription is recurring and not on trial.
     *
     * @return bool
     */
    public function recurring()
    {
        return !$this->onTrial() && !$this->canceled();
    }

    /**
     * Filter query by recurring.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeRecurring($query)
    {
        $query->notOnTrial()->notCanceled();
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
    {
        return !is_null($this->ends_at);
    }

    /**
     * Filter query by canceled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeCanceled($query)
    {
        $query->whereNotNull('ends_at');
    }

    /**
     * Filter query by not canceled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotCanceled($query)
    {
        $query->whereNull('ends_at');
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function ended()
    {
        return $this->canceled() && !$this->onGracePeriod();
    }

    /**
     * Filter query by ended.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeEnded($query)
    {
        $query->canceled()->notOnGracePeriod();
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Filter query by on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function hasExpiredTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Filter query by expired trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeExpiredTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

    /**
     * Filter query by not on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnTrial($query)
    {
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Filter query by on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnGracePeriod($query)
    {
        $query->whereNotNull('ends_at')->where('ends_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnGracePeriod($query)
    {
        $query->whereNull('ends_at')->orWhere('ends_at', '<=', Carbon::now());
    }

    /**
     * Force the trial to end immediately.
     *
     * This method must be combined with swap, resume, etc.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->trial_ends_at = null;

        return $this;
    }

    /**
     * Force the subscription's trial to end immediately.
     *
     * @return $this
     */
    public function endTrial()
    {
        if (is_null($this->trial_ends_at)) {
            return $this;
        }

        $this->trial_ends_at = null;

        $this->save();

        return $this;
    }

    /**
     * Extend an existing subscription's trial period.
     *
     * @param  \Carbon\CarbonInterface  $date
     * @return $this
     */
    public function extendTrial(CarbonInterface $date)
    {
        if (!$date->isFuture()) {
            throw new InvalidArgumentException("Extending a subscription's trial requires a date in the future.");
        }

        $this->trial_ends_at = $date;

        $this->save();

        return $this;
    }

    public function swap($plan): self
    {
        if (empty($plan)) {
            throw new InvalidArgumentException('Please provide a plan when swapping.');
        }

        $plan = Plan::find($plan);

        // If plans does not have the same billing frequency
        // (e.g., interval and interval_count) we will update
        // the billing dates starting today, and since we are basically creating
        // a new billing cycle, the usages data will be cleared.
        if ($this->plan->interval !== $plan->interval || $this->plan->interval_count !== $plan->interval_count) {
            $this->setPeriod($plan->interval->value, $plan->interval_count);
        }

        // Attach new plan to subscription
        $this->plan()->associate($plan);
        $this->save();

        $this->syncUsages();
        $this->generateInvoice(true);

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @throws LogicException
     *
     * @return $this
     */
    public function renew(): self
    {
        if ($this->ended()) {
            throw new LogicException('Unable to renew canceled ended subscription.');
        }

        $subscription = $this;

        DB::transaction(function () use ($subscription): void {
            // Clear usages data
            $subscription->usages()->delete();

            // Renew period
            $subscription->setPeriod();
            $subscription->save();

            $subscription->generateInvoice();
        });

        return $this;
    }

    /**
     * Cancel the subscription at the end of the billing period.
     *
     * @return $this
     */
    public function cancel()
    {
        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = $this->plan->getResetDate($this->created_at);
        }

        $this->save();

        return $this;
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * @param  \DateTimeInterface  $endsAt
     * @return $this
     */
    public function cancelAt(DateTimeInterface $endsAt)
    {
        if ($endsAt instanceof DateTimeInterface) {
            $this->ends_at = $endsAt->getTimestamp();
        }

        $this->status = static::STATUS_CANCELED;

        $this->save();

        return $this;
    }

    /**
     * Cancel the subscription immediately without invoicing.
     *
     * @return $this
     */
    public function cancelNow()
    {
        $this->fill([
            'status' => static::STATUS_CANCELED,
            'ends_at' => Carbon::now(),
        ])->save();

        return $this;
    }

    /**
     * Resume the canceled subscription.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function resume()
    {
        if (!$this->onGracePeriod()) {
            throw new LogicException('Unable to resume subscription that is not within grace period.');
        }

        // Finally, we will remove the ending timestamp from the user's record in the
        // local database to indicate that the subscription is active again and is
        // no longer "canceled". Then we shall save this record in the database.
        $this->fill([
            'status' => static::STATUS_ACTIVE,
            'ends_at' => null,
        ])->save();

        return $this;
    }

    public function cancelDowngrade()
    {
        $this->update([
            'next_plan' => null,
            'is_downgrade' => false,
        ]);

        return $this;
    }

    /**
     * Get the latest invoice associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Coderstm::$invoiceModel)->latestOfMany();
    }

    /**
     * Get the latest invoice associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestPaidInvoice(): HasOne
    {
        return $this->hasOne(Coderstm::$invoiceModel)->paid()->latestOfMany();
    }

    /**
     * Get all of the invoices for the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Coderstm::$invoiceModel);
    }

    /**
     * Determine if the subscription has an incomplete payment.
     *
     * @return bool
     */
    public function hasIncompletePayment()
    {
        return $this->pastDue() || $this->incomplete();
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Apply a coupon to the subscription.
     *
     * @param  string  $coupon
     * @return void
     */
    public function withCoupon($coupon): self
    {
        if ($coupon = Coupon::findByCode($coupon)) {
            $this->coupon()->associate($coupon);
        }

        return $this;
    }

    /**
     * Specify the number of days of the trial.
     *
     * @param  int  $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        $this->trial_ends_at = Carbon::now()->addDays($trialDays);

        return $this;
    }

    /**
     * Specify the ending date of the trial.
     *
     * @param  \Carbon\Carbon|\Carbon\CarbonInterface  $trialUntil
     * @return $this
     */
    public function trialUntil($trialUntil)
    {
        $this->trial_ends_at = $trialUntil;

        return $this;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan');
    }

    public function hasNexPlan()
    {
        return !is_null($this->next_plan);
    }

    public function planCanceled()
    {
        return $this->morphOne(Log::class, 'logable')
            ->where('type', 'plan-canceled')
            ->orderBy('created_at', 'desc');
    }

    public function pay($paymentMethod)
    {
        if (empty($paymentMethod)) {
            throw new InvalidArgumentException('Please provide a payment method.');
        }

        try {
            if ($this->pastDue() || $this->hasIncompletePayment()) {
                $invoice = $this->latestInvoice;
                $invoice->markAsPaid($paymentMethod, [
                    'note' => 'Marked the manual payment as received',
                ]);
            }
        } finally {
            $this->update([
                'status' => static::STATUS_ACTIVE,
            ]);

            $this->syncUsages();
        }

        return $this;
    }

    public function syncUsages()
    {
        if ($this->latestInvoice->wasRecentlyCreated) {
            $this->usages()->delete();
        } else {
            $this->syncOrResetUsages();
        }
    }

    public function renderNotification($type, $shortCodes = []): Notification
    {
        $template = Notification::default($type);
        $userShortCodes = $this->user->getShortCodes() ?? [];

        $shortCodes = array_merge($shortCodes, $userShortCodes, [
            '{{PLAN}}' => optional($this->plan)->label,
            '{{PLAN_PRICE}}' => $this->plan->formatPrice(),
            '{{BILLING_CYCLE}}' => optional($this->plan)->interval->value,
            '{{ENDS_AT}}' => $this->ends_at ? $this->ends_at->format('d M, Y') : '',
        ]);

        return $template->fill([
            'subject' => replace_short_code($template->subject, $shortCodes),
            'content' => replace_short_code($template->content, $shortCodes),
        ]);
    }

    public function sendPushNotify($type, $shortCodes = [])
    {
        try {
            $template = $this->renderNotification($type, $shortCodes);

            dispatch(new SendPushNotification($this->user, [
                'title' => $template->subject,
                'body' => html_text($template->content)
            ], [
                'route' => user_route("/billing"),
            ]));

            dispatch(new SendWhatsappNotification($this->user, "{$template->subject}\n\n{$template->content}"));
        } catch (\Exception $e) {
            //throw $e;
            report($e);
        }
    }

    /**
     * Set new subscription period.
     *
     * @param string $interval
     * @param int|null $count
     * @param Carbon|null $dateFrom
     *
     * @return $this
     */
    protected function setPeriod(string $interval = '', int $count = null, ?Carbon $dateFrom = null): self
    {
        if (empty($interval)) {
            $interval = $this->plan->interval->value;
        }

        if (empty($count)) {
            $count = $this->plan->count;
        }

        $period = new Period(
            $interval,
            $count,
            $dateFrom ?? Carbon::now()
        );

        $this->starts_at = $period->getStartDate();
        $this->expires_at = $period->getEndDate();

        return $this;
    }

    protected function discount()
    {
        // Check if coupon exists
        if ($coupon = $this->coupon) {
            return [
                'type' => $coupon->fixed ? 'fixed_amount' : 'percentage',
                'value' => $coupon->fixed ? $coupon->amount_off : $coupon->percent_off,
                'description' => $coupon->name,
            ];
        }
        return null;
    }

    public function upcomingInvoice($start = false, $dateFrom = null): InvoiceRepository
    {
        $plan = $this->nextPlan ?? $this->plan;
        $period = new Period(
            $plan->interval->value,
            $plan->interval_count,
            $dateFrom ?? $this->dateFrom()
        );

        return new InvoiceRepository([
            'user_id' => $this->user_id,
            'subscription_id' => $this->id,
            'due_date' => $start ? $this->dateFrom() : $period->getEndDate(),
            'billing_address' => $this->user->address->toArray(),
            'currency' => config('cashier.currency'),
            'collect_tax' => true,
            'line_items' => $this->generateLineItems($plan, $period),
            'discount' => $this->discount(),
        ]);
    }

    protected function dateFrom()
    {
        return optional($this->latestPaidInvoice)->due_date ?? $this->created_at;
    }

    protected function generateLineItems(Plan $plan, $period)
    {
        $fromDate = Carbon::parse($period->getStartDate())->format('M d, Y');
        $toDate = Carbon::parse($period->getEndDate())->format('M d, Y');
        $interval = $plan->interval->value;
        $amount = $plan->formatPrice();
        $title = "$plan->label  (at $amount / $interval)";

        return [
            [
                'title' => $title,
                'description' => "$fromDate - $toDate",
                'plan_id' => $plan->id,
                'price' => $plan->price,
                'total' => $plan->price,
                'quantity' => 1,
                'options' => ['title' => $title]
            ]
        ];
    }

    protected function generateInvoice($start = false): Invoice
    {
        $invoice = Invoice::modifyOrCreate(new Resource($this->upcomingInvoice($start)->toArray()));

        if ($invoice->isPaid()) {
            $this->status = static::STATUS_ACTIVE;
        } else {
            $this->status = static::STATUS_INCOMPLETE;
        }

        $this->save();

        return $invoice;
    }

    protected static function newFactory()
    {
        return SubscriptionFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (empty($model->status)) {
                $model->status = static::STATUS_INCOMPLETE;
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
