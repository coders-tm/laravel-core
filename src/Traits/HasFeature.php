<?php

namespace Coderstm\Traits;

use Coderstm\Models\Plan\Usage;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasFeature
{
    /**
     * Get all of the usages for the Subscription
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class);
    }

    /**
     * Determine if the feature can be used.
     */
    public function canUseFeature(string $featureSlug): bool
    {
        $featureValue = $this->getFeatureValue($featureSlug);
        $usage = $this->usages()->byFeatureSlug($featureSlug)->first();

        if (!$usage) {
            return (bool) $featureValue;
        }

        // If the feature value is zero, let's return false since
        // there's no uses available. (useful to disable countable features)
        if ($usage->expired() || is_null($featureValue)) {
            return false;
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
        $feature = $this->price->plan->features()->where('slug', $featureSlug)->first();
        return $feature->value ?? null;
    }

    /**
     * Record feature usage.
     */
    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true): Usage
    {
        $feature = $this->price->plan->features()->where('slug', $featureSlug)->first();

        $usage = $this->usages()->firstOrNew([
            'slug' => $feature->slug,
        ]);

        // Set expiration date when the usage record is new or doesn't have one.
        if (is_null($usage->reset_at)) {
            // Set date from subscription creation date so the reset
            // period match the period specified by the subscription's plan.
            $usage->reset_at = $feature->getResetDate($this->created_at, $this->price->interval->value);
        } elseif ($usage->expired()) {
            // If the usage record has been expired, let's assign
            // a new expiration date and reset the uses to zero.
            $usage->reset_at = $feature->getResetDate($usage->reset_at, $this->price->interval->value);
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
            $feature = $this->price->plan->features()->where('slug', $usage->slug)->first();
            if ($usage->expired()) {
                $usage->used = 0;
            }
            $usage->reset_at = $feature->getResetDate($this->created_at, $this->price->interval->value);
            $usage->save();
        });
    }

    /**
     * Reduce usage.
     */
    public function reduceFeatureUsage(string $featureSlug, int $uses = 1): ?Usage
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
