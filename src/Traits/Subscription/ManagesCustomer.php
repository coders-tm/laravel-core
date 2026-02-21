<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Coderstm;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Arr;
use Laravel\Cashier\Cashier;

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
        } catch (\Throwable $e) {
            logger()->error('Error checking feature usage: '.$e->getMessage());

            return false;
        }
    }

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true)
    {
        try {
            return $this->subscription()?->recordFeatureUsage($featureSlug, $uses, $incremental);
        } catch (\Throwable $e) {
            logger()->error('Error recording feature usage: '.$e->getMessage());

            return false;
        }
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1)
    {
        try {
            return $this->subscription()?->reduceFeatureUsage($featureSlug, $uses);
        } catch (\Throwable $e) {
            logger()->error('Error reducing feature usage: '.$e->getMessage());

            return false;
        }
    }

    public function getFeatureRemainings(string $featureSlug): int
    {
        try {
            return $this->subscription()?->getFeatureRemainings($featureSlug) ?? 0;
        } catch (\Throwable $e) {
            logger()->error('Error getting feature remainings: '.$e->getMessage());

            return 0;
        }
    }

    public function getFeatureValue(string $featureSlug, $default = null)
    {
        try {
            return $this->subscription()?->getFeatureValue($featureSlug) ?? $default;
        } catch (\Throwable $e) {
            logger()->error('Error getting feature value: '.$e->getMessage());

            return $default;
        }
    }

    public function preferredCurrency()
    {
        return config('app.currency');
    }

    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->preferredCurrency());
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Coderstm::$orderModel, 'customer_id');
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Coderstm::$orderModel, 'customer_id')->latest();
    }

    protected function billingAddress(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'phone' => $this->phone_number, 'address' => Arr::only($this->address->toArray(), ['line1', 'line2', 'city', 'state', 'postal_code'])];
    }

    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(Coderstm::$planModel, Coderstm::$subscriptionModel, $this->getForeignKey(), 'id', 'id', (new Coderstm::$planModel)->getForeignKey())->orderByDesc('created_at');
    }
}
