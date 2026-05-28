<?php

namespace Coderstm\Database\Factories\Shop\Product;

use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Variant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $price = rand(100, 300);

        return [
            'price' => $price,
            'cost_per_item' => $price - 30,
            'track_inventory' => rand(0, 1),
            'out_of_stock_track_inventory' => rand(0, 1),
        ];
    }

    public function isDefault()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_default' => true,
            ];
        });
    }

    public function taxable()
    {
        return $this->state(function (array $attributes) {
            return [
                'taxable' => true,
            ];
        });
    }

    public function nonTaxable()
    {
        return $this->state(function (array $attributes) {
            return [
                'taxable' => false,
            ];
        });
    }

    public function forProduct($productId)
    {
        return $this->state(function (array $attributes) use ($productId) {
            return [
                'product_id' => $productId,
            ];
        });
    }
}
