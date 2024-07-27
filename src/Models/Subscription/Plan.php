<?php

namespace Coderstm\Models\Subscription;

use Coderstm\Coderstm;
use Coderstm\Traits\Core;
use Coderstm\Services\Period;
use Spatie\Sluggable\HasSlug;
use Illuminate\Support\Carbon;
use Coderstm\Enum\PlanInterval;
use Spatie\Sluggable\SlugOptions;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Subscription\Feature;
use function Illuminate\Events\queueable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    use Core, SerializeDate, HasSlug;

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    protected $fillable = [
        'label',
        'description',
        'note',
        'is_active',
        'interval',
        'interval_count',
        'currency',
        'price',
        'trial_days',
    ];

    protected $appends = ['feature_lines', 'price_formated'];

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
            get: fn () => !empty($this->description) ? explode("\n", $this->description) : [],
        );
    }

    protected function priceFormated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatPrice(),
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

    protected function formatAmount($amount)
    {
        return format_amount($amount, $this->currency);
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (empty($model->currency)) {
                $model->currency = config('cashier.currency');
            }
        });

        static::updated(queueable(function ($model) {
            if ($model->wasChanged('is_active') && !$model->is_active) {
                foreach ($model->subscriptions()->cursor() as $subscription) {
                    try {
                        $subscription->cancel();
                        $subscription->logs()->create([
                            'type' => 'plan-canceled',
                            'message' => 'Subscription has been canceled due to plan deactivation!'
                        ]);
                    } catch (\Exception $e) {
                        $subscription->logs()->create([
                            'type' => 'plan-cancel-failed',
                            'message' => $e->getMessage()
                        ]);
                    }
                }
            }
        }));
    }
}
