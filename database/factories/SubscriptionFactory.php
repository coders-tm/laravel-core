<?php

namespace Coderstm\Database\Factories;

use Coderstm\Coderstm;
use DateTimeInterface;
use Coderstm\Models\Subscription;
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
            'plan_id' => (Coderstm::$planModel)::factory(),
            'type' => 'default',
            'status' => Subscription::STATUS_ACTIVE,
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
            'status' => Subscription::STATUS_ACTIVE,
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
            'status' => Subscription::STATUS_TRIALING,
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
            'status' => Subscription::STATUS_CANCELED,
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
            'status' => Subscription::STATUS_INCOMPLETE,
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
            'status' => Subscription::STATUS_INCOMPLETE_EXPIRED,
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
            'status' => Subscription::STATUS_PAST_DUE,
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
            'status' => Subscription::STATUS_UNPAID,
        ]);
    }
}
