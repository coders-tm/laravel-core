<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Laravel\Cashier\Cashier;
use Coderstm\Enum\PlanInterval;
use Coderstm\Models\Plan\Price;
use Coderstm\Models\Plan\Feature;
use Coderstm\Traits\SerializeDate;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Cashier\Subscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function Illuminate\Events\queueable;

class Plan extends Model
{
    use Core, SerializeDate;

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    protected $fillable = [
        'label',
        'description',
        'note',
        'is_active',
        'is_custom',
        'interval',
        'default_interval',
        'interval_count',
        'custom_fee',
        'monthly_fee',
        'yearly_fee',
        'stripe_id',
    ];

    protected $appends = ['feature_lines'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'interval' => PlanInterval::class,
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function subscriptions()
    {
        return $this->hasManyThrough(
            Subscription::class,
            Price::class,
            'plan_id', // Foreign key on Price model
            'stripe_price',    // Foreign key on Subscription model
            'id',       // Local key on Plan model
            'stripe_id'     // Local key on Price model
        )->active();
    }

    public function getFeatureLinesAttribute()
    {
        return !empty($this->description) ? explode("\n", $this->description) : [];
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    public function syncFeatures(Collection $items)
    {
        // delete removed features
        $this->features()->whereNotIn('id', $items->pluck('id')->filter())->delete();

        // create or updated new features
        $items->map(function ($item) {
            return (object) $item;
        })->each(function ($item) {
            $this->features()->updateOrCreate([
                'id' => optional($item)->id,
            ], [
                'label' => optional($item)->label,
                'description' => optional($item)->description,
                'value' => optional($item)->value,
            ]);
        });

        return $this;
    }

    public static function create(array $attributes = [])
    {
        try {
            // create a product for the plan in gateway
            $product = static::createStripeProduct($attributes);

            // Call the parent create method to save the model
            $plan = (new static)->fill(collect($attributes)->only((new static)->getFillable())->toArray());
            $plan->stripe_id = $product->id;
            $plan->save();

            $prices = [];
            $optional = optional((object) $attributes);
            if ($plan->is_custom) {
                $prices[] = static::createPrice($plan, [
                    'amount' => $attributes['custom_fee'],
                    'interval' => $optional->interval,
                    'interval_count' => $optional->interval_count ?? 1,
                ]);
            } else {
                $prices[] = static::createPrice($plan, [
                    'amount' => $attributes['monthly_fee'],
                    'interval' => PlanInterval::MONTH->value,
                ]);
                $prices[] = static::createPrice($plan, [
                    'amount' => $attributes['yearly_fee'],
                    'interval' => PlanInterval::YEAR->value,
                ]);
            }

            // Attach the prices to the plan
            $plan->prices()->saveMany($prices);

            return $plan;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function hasStripeId()
    {
        return !is_null($this->stripe_id);
    }

    public function createAsStripePlan()
    {
        if (!$this->hasStripeId()) {
            $product = static::createStripeProduct($this->toArray());
            $this->stripe_id = $product->id;
            $this->save();
        }
        return $this;
    }

    protected static function createPrice($plan, $options = [])
    {
        $optional = optional((object) $options);
        $price = Cashier::stripe()->prices->create([
            'nickname' => $plan->label,
            'product' => $plan->stripe_id,
            'unit_amount' => $optional->amount * 100,
            'currency' => config('cashier.currency'),
            'recurring' => [
                'interval' => $optional->interval,
                'interval_count' => $optional->interval_count ?? 1
            ],
        ]);

        return $plan->prices()->create([
            'amount' => $optional->amount,
            'stripe_id' => $price->id,
            'interval' => $optional->interval,
            'interval_count' => $optional->interval_count ?? 1,
        ]);
    }

    protected static function createStripeProduct(array $attributes = [])
    {
        $optional = optional((object) $attributes);
        return Cashier::stripe()->products->create([
            'name' => $optional->label,
            'description' => $optional->description ?? "",
        ]);
    }

    protected static function booted()
    {
        parent::booted();
        static::updated(queueable(function ($model) {
            if ($model->hasStripeId()) {
                Cashier::stripe()->products->update($model->stripe_id, [
                    'name' => $model->label,
                    'description' => $model->description ?? "",
                ]);
            }
            if ($model->wasChanged('is_active') && !$model->is_active) {
                foreach ($model->subscriptions as $subscription) {
                    try {
                        $subscription->cancel();
                        $subscription->logs()->create([
                            'type' => 'plan-canceled',
                            'message' => 'Subscription has been canceled due to plan deactivation!'
                        ]);
                    } catch (\Exception $e) {
                        //throw $e;
                    }
                }
            }
        }));
    }
}
