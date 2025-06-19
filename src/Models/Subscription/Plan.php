<?php

namespace Coderstm\Models\Subscription;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\PlanFactory;
use Coderstm\Traits\Core;
use Coderstm\Services\Period;
use Spatie\Sluggable\HasSlug;
use Illuminate\Support\Carbon;
use Coderstm\Enum\PlanInterval;
use Spatie\Sluggable\SlugOptions;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Subscription\Feature;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    use Core, SerializeDate, HasSlug;

    protected $fillable = [
        'label',
        'description',
        'is_active',
        'default_interval',
        'interval',
        'interval_count',
        'price',
        'trial_days',
    ];

    protected $appends = ['feature_lines', 'price_formatted', 'interval_label'];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_days' => 'integer',
        'interval' => PlanInterval::class,
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Coderstm::$subscriptionModel)->active();
    }

    public function getResetDate(?Carbon $dateFrom): Carbon
    {
        $period = new Period($this->interval->value, 1, $dateFrom ?? now());
        return $period->getEndDate();
    }

    protected function featureLines(): Attribute
    {
        return Attribute::make(
            get: fn() => !empty($this->description) ? explode("\n", $this->description) : [],
        );
    }

    protected function priceFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->formatPrice(),
        );
    }

    protected function intervalLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->formatInterval(),
        );
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')->withPivot('value');
    }

    public function syncFeatures(array $items = [])
    {
        $features = collect($items)->mapWithKeys(function ($value, $key) {
            if ($feature = Feature::findBySlug($key)) {
                return [$feature->id => [
                    'value' => $value,
                ]];
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
        return SlugOptions::create()
            ->generateSlugsFrom('label')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public function formatPrice()
    {
        return $this->formatAmount($this->price);
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

    protected static function newFactory()
    {
        return PlanFactory::new();
    }
}
