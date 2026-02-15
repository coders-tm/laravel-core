<?php

namespace Coderstm\Traits;

use Coderstm\Exceptions\Plan\FeatureNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasFeature
{
    public function features(): HasMany
    {
        return $this->hasMany(\Coderstm\Models\Subscription\SubscriptionFeature::class);
    }

    public function usagesToArray()
    {
        return $this->features->toArray();
    }

    public function canUseFeature(string $featureSlug): bool
    {
        if (! $this->valid()) {
            logger()->info("Subscription is not valid for using feature {$featureSlug}");

            return false;
        }
        $subscriptionFeature = $this->features()->where('slug', $featureSlug)->first();
        if (! $subscriptionFeature) {
            throw new FeatureNotFoundException;
        }

        return $subscriptionFeature->canUse();
    }

    public function getFeatureUsage(string $featureSlug): int
    {
        $subscriptionFeature = $this->features()->where('slug', $featureSlug)->first();

        return $subscriptionFeature ? $subscriptionFeature->used : 0;
    }

    public function getFeatureRemainings(string $featureSlug): int
    {
        if (! $this->valid()) {
            return 0;
        }
        $subscriptionFeature = $this->features()->where('slug', $featureSlug)->first();

        return $subscriptionFeature ? $subscriptionFeature->remaining : 0;
    }

    public function getFeatureValue(string $featureSlug)
    {
        $subscriptionFeature = $this->features()->where('slug', $featureSlug)->first();

        return $subscriptionFeature ? $subscriptionFeature->value : null;
    }

    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true)
    {
        $subscriptionFeature = $this->features()->where('slug', $featureSlug)->first();
        if (! $subscriptionFeature) {
            throw new FeatureNotFoundException;
        }
        if ($subscriptionFeature->isBoolean()) {
            return false;
        }
        if (is_null($subscriptionFeature->reset_at) && $subscriptionFeature->resetable) {
            $subscriptionFeature->reset_at = $this->expires_at;
        } elseif ($subscriptionFeature->expired()) {
            $subscriptionFeature->reset_at = $this->expires_at;
            $subscriptionFeature->used = 0;
        }

        return $subscriptionFeature->recordUsage($uses, $incremental);
    }

    public function syncFeaturesFromPlan(): void
    {
        if (! $this->plan) {
            return;
        }
        $this->plan->load('features');
        $planFeatures = $this->plan->features;
        $planFeatureSlugs = $planFeatures->pluck('slug')->toArray();
        $this->features()->whereNotIn('slug', $planFeatureSlugs)->delete();
        foreach ($planFeatures as $planFeature) {
            $this->features()->updateOrCreate(['slug' => $planFeature->slug], ['label' => $planFeature->label, 'type' => $planFeature->type, 'resetable' => $planFeature->resetable, 'value' => $planFeature->pivot->value, 'reset_at' => $this->expires_at, 'used' => 0]);
        }
    }

    public function syncOrResetUsages(): void
    {
        $this->features()->where('resetable', 1)->each(function ($subscriptionFeature) {
            if ($subscriptionFeature->expired()) {
                $subscriptionFeature->used = 0;
                $subscriptionFeature->reset_at = $this->expires_at;
            }
            $subscriptionFeature->save();
        });
    }

    public function resetUsagesForRenewal(): void
    {
        $this->features()->where('resetable', 1)->update(['used' => 0, 'reset_at' => $this->expires_at]);
    }

    public function resetUsages(): void
    {
        $this->features()->update(['used' => 0, 'reset_at' => $this->expires_at]);
    }

    public function reduceFeatureUsage(string $featureSlug, int $uses = 1)
    {
        $subscriptionFeature = $this->features()->where('slug', $featureSlug)->first();
        if (is_null($subscriptionFeature)) {
            return null;
        }

        return $subscriptionFeature->reduceUsage($uses);
    }
}
