<?php

namespace Coderstm\Models\Cashier;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Traits\Logable;
use Laravel\Cashier\Cashier;
use InvalidArgumentException;
use Coderstm\Traits\HasFeature;
use Coderstm\Models\Notification;
use Coderstm\Traits\SerializeDate;
use Coderstm\Jobs\SendPushNotification;
use Coderstm\Jobs\SendWhatsappNotification;
use Coderstm\Events\Cashier\SubscriptionProcessed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    use HasFeature, Logable, SerializeDate;

    /**
     * The default price model class name.
     *
     * @var string
     */
    protected $priceModel = 'Coderstm\\Models\\Plan\\Price';

    /**
     * The default invoice model class name.
     *
     * @var string
     */
    protected $invoiceModel = null;

    protected $fillable = [
        'user_id',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'is_downgrade',
        'is_upgrade',
        'schedule',
        'next_plan',
        'previous_plan',
        'trial_ends_at',
        'ends_at',
        'cancels_at',
        'expires_at',
    ];

    protected $with = [
        'price.plan',
        'price.features',
        'usages',
    ];

    protected $dispatchesEvents = [
        'created' => SubscriptionProcessed::class,
        'updated' => SubscriptionProcessed::class,
    ];

    protected $casts = [
        'ends_at' => 'datetime',
        'quantity' => 'integer',
        'is_upgrade' => 'boolean',
        'is_downgrade' => 'boolean',
        'trial_ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $appends = ['is_valid'];

    public function price(): BelongsTo
    {
        return $this->belongsTo($this->priceModel, 'stripe_price', 'stripe_id');
    }

    public function nextPrice(): BelongsTo
    {
        return $this->belongsTo($this->priceModel, 'next_plan', 'stripe_id');
    }

    public function previousPrice(): BelongsTo
    {
        return $this->belongsTo($this->priceModel, 'previous_plan', 'stripe_id');
    }

    public function planCanceled()
    {
        return $this->morphOne(Log::class, 'logable')
            ->where('type', 'plan-canceled')
            ->orderBy('created_at', 'desc');
    }

    public function hasSchedule()
    {
        return !is_null($this->schedule);
    }

    public function hasNexPlan()
    {
        return !is_null($this->next_plan);
    }

    public function hasPreviousPlan()
    {
        return !is_null($this->previous_plan);
    }

    public function hasManualUpgrade()
    {
        return $this->is_upgrade;
    }

    public function releaseSchedule()
    {
        try {
            if ($this->hasSchedule()) {
                Cashier::stripe()->subscriptionSchedules->release($this->schedule);
                $this->fill([
                    'schedule' => null,
                    'is_downgrade' => false,
                ])->save();
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getIsValidAttribute()
    {
        return $this->valid() ?: false;
    }

    public function swap($prices, array $options = [])
    {
        if (empty($prices = (array) $prices)) {
            throw new InvalidArgumentException('Please provide at least one price when swapping.');
        }

        $this->guardAgainstIncomplete();

        $items = $this->mergeItemsThatShouldBeDeletedDuringSwap(
            $this->parseSwapPrices($prices)
        );

        $stripeSubscription = $this->owner->stripe()->subscriptions->update(
            $this->stripe_id,
            $this->getSwapOptions($items, $options)
        );

        /** @var \Stripe\SubscriptionItem $firstItem */
        $firstItem = $stripeSubscription->items->first();
        $isSinglePrice = $stripeSubscription->items->count() === 1;
        $metadata = isset($options['metadata']) ? $options['metadata'] : [];

        $this->fill(array_merge(
            $metadata,
            [
                'stripe_status' => $stripeSubscription->status,
                'stripe_price' => $isSinglePrice ? $firstItem->price->id : null,
                'quantity' => $isSinglePrice ? ($firstItem->quantity ?? null) : null,
                'ends_at' => null,
            ]
        ))->save();

        $stripePrices = [];

        foreach ($stripeSubscription->items as $item) {
            $stripePrices[] = $item->price->id;

            $this->items()->updateOrCreate([
                'stripe_id' => $item->id,
            ], [
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity ?? null,
            ]);
        }

        // Delete items that aren't attached to the subscription anymore...
        $this->items()->whereNotIn('stripe_price', $stripePrices)->delete();

        $this->unsetRelation('items');

        $this->handlePaymentFailure($this);

        return $this;
    }

    /**
     * Fetches upcoming invoice for this subscription.
     *
     * @param  array  $options
     * @return \Laravel\Cashier\Invoice|null
     */
    public function upcomingInvoice(array $options = [])
    {
        if ($this->canceled() && !$this->hasSchedule()) {
            return null;
        }

        return $this->owner->upcomingInvoice(array_merge([
            'subscription' => $this->stripe_id,
        ], $options));
    }

    public function pay($paymentMethod)
    {
        if (empty($paymentMethod)) {
            throw new InvalidArgumentException('Please provide a payment method.');
        }

        try {
            if ($this->pastDue() || $this->hasIncompletePayment()) {
                $invoice = $this->latestInvoice();
                $invoice->pay([
                    'payment_method' => $paymentMethod
                ]);
            }
        } catch (\Exception $e) {
            $this->handlePaymentFailure($this);
        } finally {
            $stripeSubscription = $this->asStripeSubscription();

            $this->update([
                'stripe_status' => $stripeSubscription->status,
            ]);

            $this->syncLatestInvoice();
        }

        return $this;
    }

    public function paidOutOfBand($note = 'Cash')
    {
        try {
            if ($this->pastDue() || $this->hasIncompletePayment()) {
                $invoice = $this->latestInvoice();
                $invoice->pay([
                    'paid_out_of_band' => true
                ]);
            } else if ($this->onTrial()) {
                $this->owner->creditBalance($this->upcomingInvoice()->amount_due, $note);
                $this->endTrial();
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $stripeSubscription = $this->asStripeSubscription();

            $this->update([
                'stripe_status' => $stripeSubscription->status,
            ]);

            $this->syncLatestInvoice();
        }
    }

    public function syncLatestInvoice()
    {
        $invoice = $this->latestInvoice();
        $invoiceModel = $this->invoiceModel ?? Coderstm::$invoiceModel;
        $appInvoice = $invoiceModel::createFromStripe($invoice, ['subscription_id' => $this->id]);

        if ($appInvoice->wasRecentlyCreated) {
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
            '{{PLAN}}' => optional($this->price)->label,
            '{{PLAN_PRICE}}' => format_amount(optional($this->price)->amount * 100),
            '{{BILLING_CYCLE}}' => optional($this->price)->interval->value,
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
                'route' => "/billing",
            ]));

            dispatch(new SendWhatsappNotification($this->user, "{$template->subject}\n\n{$template->content}"));
        } catch (\Exception $e) {
            //throw $e;
            report($e);
        }
    }
}
