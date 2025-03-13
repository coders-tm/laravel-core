<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Coderstm;
use Illuminate\Support\Arr;
use Laravel\Cashier\Cashier;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

trait ManagesCustomer
{
    public function isSubscribed(): bool
    {
        return $this->onTrial() || $this->subscribed();
    }

    public function hasCancelled(): bool
    {
        return $this->subscribed() ? $this->subscription()->canceled() : false;
    }

    public function canUseFeature(string $featureSlug): ?bool
    {
        try {
            return $this->subscription()?->canUseFeature($featureSlug);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true)
    {
        try {
            return $this->subscription()?->recordFeatureUsage($featureSlug, $uses, $incremental);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1)
    {
        try {
            return $this->subscription()?->reduceFeatureUsage($featureSlug, $uses);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFeatureRemainings(string $featureSlug): int
    {
        try {
            return $this->subscription()?->getFeatureRemainings($featureSlug);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get the Stripe supported currency used by the customer.
     *
     * @return string
     */
    public function preferredCurrency()
    {
        return config('cashier.currency');
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->preferredCurrency());
    }

    /**
     * Get all of the invoices for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Coderstm::$orderModel, 'customer_id');
    }

    /**
     * Get the latest invoices for the User
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Coderstm::$orderModel, 'customer_id')->latest();
    }

    protected function billingAddress(): array
    {
        return [
            'name' =>  $this->name,
            'email' =>  $this->email,
            'phone' =>  $this->phone_number,
            'address' =>  Arr::only($this->address->toArray(), [
                'line1',
                'line2',
                'city',
                'state',
                'postal_code'
            ]),
        ];
    }

    /**
     * The plan that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Coderstm::$planModel,
            Coderstm::$subscriptionModel,
            $this->getForeignKey(),
            'id',
            'id',
            (new Coderstm::$planModel)->getForeignKey()
        )->orderByDesc('created_at');
    }
}
