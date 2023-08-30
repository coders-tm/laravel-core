<?php

namespace Coderstm\Models\Cashier;

use Laravel\Cashier\Cashier;
use Coderstm\Models\Plan\Price;
use Coderstm\Traits\HasFeature;
use Coderstm\Events\Cashier\SubscriptionProcessed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription as CashierSubscription;
use InvalidArgumentException;

class Subscription extends CashierSubscription
{
    use HasFeature;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'is_downgrade',
        'schedule',
        'trial_ends_at',
        'ends_at',
        'cancels_at',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'price.plan',
        'price.features',
        'usages',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => SubscriptionProcessed::class,
        'updated' => SubscriptionProcessed::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_valid'];

    /**
     * Get the price that owns the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'stripe_price', 'stripe_id');
    }


    public function releaseSchedule()
    {
        try {
            if ($this->schedule) {
                Cashier::stripe()->subscriptionSchedules->release($this->schedule);
                $this->fill([
                    'schedule' => null,
                    'is_downgrade' => false,
                ])->save();
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    /**
     * Get is valid for the Subscription
     *
     * @return bool
     */
    public function getIsValidAttribute()
    {
        return $this->valid() ?: false;
    }

    /**
     * Swap the subscription to new Stripe prices.
     *
     * @param  string|array  $prices
     * @param  array  $options
     * @return $this
     *
     * @throws \Laravel\Cashier\Exceptions\IncompletePayment
     * @throws \Laravel\Cashier\Exceptions\SubscriptionUpdateFailure
     */
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
}
