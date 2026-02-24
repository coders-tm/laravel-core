<?php

namespace Coderstm\Models\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Contracts\Currencyable;
use Coderstm\Database\Factories\PlanFactory;
use Coderstm\Enum\PlanInterval;
use Coderstm\Services\Period;
use Coderstm\Traits\Core;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Plan extends Model implements Currencyable
{
    use Core, HasSlug, SerializeDate;

    protected $fillable = ['label', 'description', 'is_active', 'default_interval', 'interval', 'interval_count', 'is_contract', 'contract_cycles', 'allow_freeze', 'freeze_fee', 'grace_period_days', 'price', 'trial_days', 'setup_fee', 'variant_id', 'metadata'];

    protected $appends = ['feature_lines', 'price_formatted', 'interval_label', 'effective_price', 'has_trial_period'];

    protected $casts = ['is_active' => 'boolean', 'is_contract' => 'boolean', 'allow_freeze' => 'boolean', 'interval_count' => 'integer', 'contract_cycles' => 'integer', 'trial_days' => 'integer', 'grace_period_days' => 'integer', 'freeze_fee' => 'decimal:2', 'setup_fee' => 'double', 'interval' => PlanInterval::class, 'metadata' => 'json'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Coderstm::$subscriptionModel)->active();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\Coderstm\Models\Shop\Product\Variant::class, 'variant_id');
    }

    public function product()
    {
        return $this->hasOneThrough(\Coderstm\Models\Shop\Product::class, \Coderstm\Models\Shop\Product\Variant::class, 'id', 'id', 'variant_id', 'product_id');
    }

    public function getResetDate(?Carbon $dateFrom): Carbon
    {
        $period = new Period($this->interval->value, 1, $dateFrom ?? now());

        return $period->getEndDate();
    }

    public function getNextBillingDate(?Carbon $dateFrom = null): Carbon
    {
        $period = new Period($this->interval->value, $this->interval_count, $dateFrom ?? now());

        return $period->getEndDate();
    }

    public function getContractEndDate(?Carbon $dateFrom = null): Carbon
    {
        $period = new Period($this->interval->value, $this->interval_count, $dateFrom ?? now());

        return $period->getEndDate();
    }

    public function getTotalBillingCycles(): ?int
    {
        if (! $this->is_contract) {
            return null;
        }

        return $this->contract_cycles;
    }

    public function isContract(): bool
    {
        return $this->is_contract;
    }

    protected function featureLines(): Attribute
    {
        return Attribute::make(get: fn () => ! empty($this->description) ? explode("\n", $this->description) : []);
    }

    protected function priceFormatted(): Attribute
    {
        return Attribute::make(get: fn () => $this->formatPrice());
    }

    protected function intervalLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->formatInterval());
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::make(get: fn () => $this->getEffectivePrice());
    }

    protected function hasTrialPeriod(): Attribute
    {
        return Attribute::make(get: fn () => $this->hasTrial());
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')->withPivot('value');
    }

    public function syncFeatures(array $items = [])
    {
        $features = collect($items)->mapWithKeys(function ($value, $key) {
            if ($feature = Feature::findBySlug($key)) {
                return [$feature->id => ['value' => $value]];
            }

            return [$key => null];
        })->filter();
        $this->features()->sync($features->toArray());

        return $this;
    }

    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    public function hasTrial(): bool
    {
        return $this->trial_days > 0;
    }

    public function getTrialEndDate(?Carbon $startDate = null): ?Carbon
    {
        if (! $this->hasTrial()) {
            return null;
        }
        $start = $startDate ?? now();

        return $start->copy()->addDays($this->trial_days);
    }

    public function getEffectivePrice(?Carbon $currentDate = null): float
    {
        $now = $currentDate ?? now();
        if ($this->hasTrial()) {
            $trialEnd = $this->getTrialEndDate();
            if ($trialEnd && $now->lte($trialEnd)) {
                return 0;
            }
        }

        return $this->price;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function activate(): self
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['is_active' => false]);

        return $this;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('label')->saveSlugsTo('slug')->preventOverwrite();
    }

    public function formatPrice()
    {
        return \Coderstm\Facades\Currency::format($this->price);
    }

    protected function formatInterval()
    {
        $interval = $this->interval->value;
        if ($this->interval_count > 1) {
            return "{$this->interval_count} {$interval}s";
        } else {
            return "{$interval}";
        }
    }

    protected function formatAmount($amount)
    {
        return format_amount($amount);
    }

    public function getFreezeFee(): float
    {
        return $this->freeze_fee ?? config('coderstm.subscription.freeze_fee', 0.0);
    }

    public function allowsFreeze(): bool
    {
        if (! $this->allow_freeze) {
            return false;
        }

        return config('coderstm.subscription.allow_freeze', true);
    }

    public function getCurrencyFields(): array
    {
        return ['price', 'freeze_fee', 'setup_fee'];
    }

    protected function setupFee(): Attribute
    {
        return Attribute::make(get: fn ($value) => $value ?? config('coderstm.subscription.setup_fee', 0.0));
    }

    protected static function newFactory()
    {
        return PlanFactory::new();
    }
}
