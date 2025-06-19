<?php

namespace Coderstm\Database\Factories;

use Coderstm\Coderstm;
use DateTimeInterface;
use Coderstm\Models\Subscription;
use Coderstm\Contracts\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $model = Coderstm::$userModel;

        return [
            (new $model)->getForeignKey() => ($model)::factory(),
            'plan_id' => Coderstm::$planModel::inRandomOrder()->first()?->id,
            'type' => 'default',
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * Mark the subscription as active.
     *
     * @return $this
     */
    public function active(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    /**
     * Mark the subscription as being within a trial period.
     *
     * @return $this
     */
    public function trialing(DateTimeInterface $trialEndsAt = null): static
    {
        return $this->state([
            'status' => SubscriptionStatus::TRIALING,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    /**
     * Mark the subscription as canceled.
     *
     * @return $this
     */
    public function canceled(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::CANCELED,
            'ends_at' => now(),
        ]);
    }

    /**
     * Mark the subscription as incomplete.
     *
     * @return $this
     */
    public function incomplete(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::INCOMPLETE,
        ]);
    }

    /**
     * Mark the subscription as incomplete where the allowed completion period has expired.
     *
     * @return $this
     */
    public function incompleteAndExpired(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::INCOMPLETE_EXPIRED,
        ]);
    }

    /**
     * Mark the subscription as being past the due date.
     *
     * @return $this
     */
    public function pastDue(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::PAST_DUE,
        ]);
    }

    /**
     * Mark the subscription as unpaid.
     *
     * @return $this
     */
    public function unpaid(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::UNPAID,
        ]);
    }
}
