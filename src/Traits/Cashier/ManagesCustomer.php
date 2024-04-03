<?php

namespace Coderstm\Traits\Cashier;

use Coderstm\Coderstm;
use Coderstm\Models\Plan\Price;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Laravel\Cashier\Concerns\ManagesCustomer as CashierManagesCustomer;

trait ManagesCustomer
{
    use CashierManagesCustomer;

    /**
     * Can the user use the application (is on trial or subscription).
     */
    public function getIsSubscribedAttribute(): bool
    {
        return $this->onTrial() || $this->subscribed('default');
    }

    /**
     * Get the has cancelled status of the user.
     *
     * @return bool
     */
    public function getHasCancelledAttribute()
    {
        return $this->subscribed('default') ? $this->subscription()->canceled() : false;
    }

    public function canUseFeature(string $featureSlug): bool
    {
        try {
            return $this->subscription()->canUseFeature($featureSlug);
        } catch (\Exception $e) {
            // return false;
            throw $e;
        }
    }

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true)
    {
        try {
            return $this->subscription()->recordFeatureUsage($featureSlug, $uses, $incremental);
        } catch (\Exception $e) {
            return false;
            // throw $e;
        }
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1)
    {
        try {
            return $this->subscription()->reduceFeatureUsage($featureSlug, $uses);
        } catch (\Exception $e) {
            return false;
            // throw $e;
        }
    }

    /**
     * Get all of the subscription invoices for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function appInvoices()
    {
        return $this->hasManyThrough(Coderstm::$invoiceModel, Coderstm::$subscriptionModel);
    }

    /**
     * Get the latest invoices for the User
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function latestAppInvoice()
    {
        return $this->hasOneThrough(Coderstm::$invoiceModel, Coderstm::$subscriptionModel)
            ->orderByDesc('created_at');
    }

    /**
     * The price that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function price(): HasOneThrough
    {
        return $this->hasOneThrough(Price::class, Coderstm::$subscriptionModel, 'user_id', 'stripe_id', 'id', 'stripe_price')
            ->orderByDesc('created_at');
    }
}
