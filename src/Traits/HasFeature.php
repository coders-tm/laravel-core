<?php

namespace Coderstm\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Coderstm\Exceptions\Plan\FeatureNotFoundException;

trait HasFeature
{
    /**
     * The default feature model class name.
     *
     * @var string
     */
    protected $featureModel = 'Coderstm\\Models\\Feature';

    /**
     * The default usage model class name.
     *
     * @var string
     */
    protected $usageModel = 'Coderstm\\Models\\Plan\\Usage';

    /**
     * Get all of the usages for the Subscription
     */
    public function usages(): HasMany
    {
        return $this->hasMany($this->usageModel, 'subscription_id');
    }

    /**
     * Get all of the usages with max credit for the Subscription
     */
    public function usagesToArray()
    {
        $usages = [];
        $planFeatures = [];

        foreach ($this->usages as $usage) {
            $usages[$usage['slug']] = $usage['used'];
        }

        foreach ($this->price->features as $feature) {
            $planFeatures[$feature['slug']] = $feature['value'];
        }

        return $this->featureModel::all()->map(function ($item)  use ($planFeatures, $usages) {
            $slug = $item->slug;
            $item->value = isset($planFeatures[$slug]) ? $planFeatures[$slug] : 0;
            $item->used = isset($usages[$slug]) ? $usages[$slug] : 0;
            return $item;
        });
    }

    /**
     * Determine if the feature can be used.
     */
    public function canUseFeature(string $featureSlug): bool
    {
        $feature = $this->featureModel::where('slug', $featureSlug)->first();
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
        $feature = $this->price->features()->where('slug', $featureSlug)->first();
        return $feature->value ?? null;
    }

    /**
     * Record feature usage.
     */
    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true)
    {
        $feature = $this->featureModel::where('slug', $featureSlug)->first();
        $planFeature = $this->price->features()->where('slug', $featureSlug)->first();

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
            $usage->reset_at = $planFeature->getResetDate($this->created_at, $this->price->interval->value);
        } elseif ($usage->expired()) {
            // If the usage record has been expired, let's assign
            // a new expiration date and reset the uses to zero.
            $usage->reset_at = $planFeature->getResetDate($usage->reset_at, $this->price->interval->value);
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
            $feature = $this->price->features()->where('slug', $usage->slug)->first();
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
