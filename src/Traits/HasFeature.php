<?php

namespace Coderstm\Traits;

use Coderstm\Models\Subscription\Usage;
use Coderstm\Models\Subscription\Feature;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Coderstm\Exceptions\Plan\FeatureNotFoundException;

trait HasFeature
{

    /**
     * Get all of the usages for the Subscription
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class, 'subscription_id');
    }

    protected function features(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->plan->features->mapWithKeys(function ($item) {
                return [$item->slug => $item->pivot->value];
            }),
        );
    }

    /**
     * Get all of the usages with max credit for the Subscription
     */
    public function usagesToArray()
    {
        $usages = [];

        foreach ($this->usages as $usage) {
            $usages[$usage['slug']] = $usage['used'];
        }

        return Feature::all()->map(function ($item)  use ($usages) {
            $slug = $item->slug;
            $item->value = isset($this->features[$slug]) ? $this->features[$slug] : 0;
            $item->used = isset($usages[$slug]) ? $usages[$slug] : 0;
            return $item;
        });
    }

    /**
     * Determine if the feature can be used.
     */
    public function canUseFeature(string $featureSlug): bool
    {
        $feature = Feature::where('slug', $featureSlug)->first();
        $featureValue = $this->getFeatureValue($featureSlug);
        $usage = $this->usages()->byFeatureSlug($featureSlug)->first();

        if (!$feature) {
            throw new FeatureNotFoundException;
        }

        if ($feature->isBoolean()) {
            return $featureValue === 1;
        } else if (!$usage) {
            return $featureValue >= 0;
        }

        // If the feature value is zero, let's return false since
        // there's no uses available. (useful to disable countable features)
        if ($usage->expired()) {
            return false;
        }

        // If feature value is explicitly set to zero, it can be used
        else if ($featureValue === 0) {
            return true;
        }

        // Check for available uses
        return $this->getFeatureRemainings($featureSlug) > 0;
    }

    /**
     * Get how many times the feature has been used.
     */
    public function getFeatureUsage(string $featureSlug): int
    {
        $usage = $this->usages()->byFeatureSlug($featureSlug)->first();
        return (!$usage || $usage->expired()) ? 0 : $usage->used;
    }

    /**
     * Get the available uses.
     */
    public function getFeatureRemainings(string $featureSlug): int
    {
        return $this->getFeatureValue($featureSlug) - $this->getFeatureUsage($featureSlug);
    }

    /**
     * Get feature value.
     */
    public function getFeatureValue(string $featureSlug)
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();
        return optional($feature?->pivot)->value ?? null;
    }

    /**
     * Record feature usage.
     */
    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true)
    {
        $feature = Feature::where('slug', $featureSlug)->first();
        $planFeature = (isset($this->features[$featureSlug]) && $this->features[$featureSlug]) || !isset($this->features[$featureSlug]);

        if (!$feature) {
            throw new FeatureNotFoundException;
        }

        if (!$planFeature || $feature->isBoolean()) return false;

        $usage = $this->usages()->firstOrNew([
            'slug' => $featureSlug,
        ]);

        // Set expiration date when the usage record is new or doesn't have one.
        if (is_null($usage->reset_at) && $feature->resetable) {
            // Set date from subscription creation date so the reset
            // period match the period specified by the subscription's plan.
            $usage->reset_at = $this->plan->getResetDate($this->created_at);
        } elseif ($usage->expired()) {
            // If the usage record has been expired, let's assign
            // a new expiration date and reset the uses to zero.
            $usage->reset_at = $this->plan->getResetDate($usage->reset_at);
            $usage->used = 0;
        }

        $usage->used = ($incremental ? $usage->used + $uses : $uses);

        $usage->save();

        return $usage;
    }

    /**
     * Sync or reset usages based on plan.
     */
    public function syncOrResetUsages(): void
    {
        $this->usages()->each(function ($usage) {
            if ($usage->expired()) {
                $usage->used = 0;
            }
            $usage->reset_at = $this->plan->getResetDate($this->created_at);
            $usage->save();
        });
    }

    /**
     * Reduce usage.
     */
    public function reduceFeatureUsage(string $featureSlug, int $uses = 1)
    {
        $usage = $this->usages()->byFeatureSlug($featureSlug)->first();

        if (is_null($usage)) {
            return null;
        }

        $usage->used = max($usage->used - $uses, 0);

        $usage->save();

        return $usage;
    }
}
