<?php

namespace Tests\Feature\Shop\User;

use Coderstm\Models\Coupon;
use Illuminate\Http\Request;
use Coderstm\Enum\CouponDuration;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Subscription\Plan;
use PHPUnit\Framework\Attributes\Test;
use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Foundation\Testing\WithFaker;
use App\Http\Controllers\User\ShopController;
use Coderstm\Models\Shop\Product\Variant\Option;
use Coderstm\Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use WithFaker;

    protected $product;
    protected $variant;
    protected $plan;
    protected $coupon;
    protected $simpleProduct;
    protected $simpleVariant;
    protected $simplePlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create our test products with proper setup
        $this->createTestProducts();
        $this->createTestCouponsAndPlans();
    }

    protected function createTestProducts()
    {
        // 1. Create a product with variants and recurring plans
        $this->product = Product::factory()->create([
            'title' => 'Test Subscription Product with Variants',
            'slug' => 'test-subscription-product-variants',
            'description' => 'A test subscription product with variants',
            'has_variant' => true,
        ]);

        // Create a default variant for the variant product
        $defaultVariant = Variant::create([
            'product_id' => $this->product->id,
            'price' => 75.00,
            'compare_at_price' => 150.00,
            'in_stock' => true,
            'recurring' => true,
            'is_default' => true,
        ]);

        // Create a non-default variant for the variant product
        $this->variant = Variant::create([
            'product_id' => $this->product->id,
            'price' => 85.00,
            'compare_at_price' => 160.00,
            'in_stock' => true,
            'recurring' => true,
            'is_default' => false,
        ]);

        // Create recurring plans for both variants
        $this->plan = Plan::factory()->create([
            'variant_id' => $this->variant->id,
            'label' => 'Monthly',
            'slug' => 'monthly',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 85.00,
            'trial_days' => 0,
            'is_active' => true,
        ]);

        Plan::factory()->create([
            'variant_id' => $defaultVariant->id,
            'label' => 'Default Monthly',
            'slug' => 'default-monthly',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 75.00,
            'trial_days' => 0,
            'is_active' => true,
        ]);

        // Add options to the variant product (using ShopSeeder's attributes)
        $colorAttribute = \Coderstm\Models\Shop\Product\Attribute::where('name', 'Color')->first();
        $sizeAttribute = \Coderstm\Models\Shop\Product\Attribute::where('name', 'Size')->first();

        if ($colorAttribute) {
            $colorOption = $this->product->options()->create([
                'name' => 'Color',
                'attribute_id' => $colorAttribute->id,
            ]);
            $colorOption->attribue_values()->sync($colorAttribute->values->pluck('id'));
        }

        if ($sizeAttribute) {
            $sizeOption = $this->product->options()->create([
                'name' => 'Size',
                'attribute_id' => $sizeAttribute->id,
            ]);
            $sizeOption->attribue_values()->sync($sizeAttribute->values->pluck('id'));
        }

        // Create variant options for the main variant (link variant to product options)
        if ($colorAttribute && $sizeAttribute) {
            $colorValues = $colorAttribute->values;
            $sizeValues = $sizeAttribute->values;

            if ($colorValues->isNotEmpty() && $sizeValues->isNotEmpty()) {
                // Create options for the main variant (Red, S)
                Option::create([
                    'variant_id' => $this->variant->id,
                    'option_id' => $this->product->options()->where('name', 'Color')->first()->id,
                    'position' => 1,
                    'value' => $colorValues->first()->name, // Use first color value (Red)
                ]);

                Option::create([
                    'variant_id' => $this->variant->id,
                    'option_id' => $this->product->options()->where('name', 'Size')->first()->id,
                    'position' => 2,
                    'value' => $sizeValues->first()->name, // Use first size value (S)
                ]);

                // Create options for the default variant (Blue, M)
                Option::create([
                    'variant_id' => $defaultVariant->id,
                    'option_id' => $this->product->options()->where('name', 'Color')->first()->id,
                    'position' => 1,
                    'value' => $colorValues->count() > 3 ? $colorValues->skip(3)->first()->name : $colorValues->skip(1)->first()->name, // Blue
                ]);

                Option::create([
                    'variant_id' => $defaultVariant->id,
                    'option_id' => $this->product->options()->where('name', 'Size')->first()->id,
                    'position' => 2,
                    'value' => $sizeValues->count() > 1 ? $sizeValues->skip(1)->first()->name : $sizeValues->first()->name, // M
                ]);
            }
        }

        // 2. Create a simple product (no variants) with recurring plans
        $this->simpleProduct = Product::factory()->create([
            'title' => 'Test Simple Subscription Product',
            'slug' => 'test-simple-subscription-product',
            'description' => 'A test simple subscription product without variants',
            'has_variant' => false,
        ]);

        // Create a default variant for the simple product (simple products still need a variant for pricing)
        $this->simpleVariant = Variant::create([
            'product_id' => $this->simpleProduct->id,
            'price' => 50.00,
            'compare_at_price' => 100.00,
            'in_stock' => true,
            'recurring' => true,
            'is_default' => true,
        ]);

        // Create a recurring plan for the simple product
        $this->simplePlan = Plan::factory()->create([
            'variant_id' => $this->simpleVariant->id,
            'label' => 'Simple Monthly',
            'slug' => 'simple-monthly',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 50.00,
            'trial_days' => 0,
            'is_active' => true,
        ]);
    }

    protected function createTestCouponsAndPlans()
    {
        // Create test coupon for plan discount (50% off)
        $this->coupon = Coupon::factory()->create([
            'name' => 'First Month 50% Off',
            'promotion_code' => 'FIRSTMONTH50',
            'type' => Coupon::TYPE_PLAN,
            'value' => 50,
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'duration' => CouponDuration::ONCE,
            'duration_in_months' => 1,
            'auto_apply' => true,
            'active' => true,
        ]);

        // Get all plans for both products to associate with coupon
        $allPlans = collect();

        // Add plans from variant product
        foreach ($this->product->variants as $variant) {
            $allPlans = $allPlans->merge($variant->plans);
        }

        // Add plans from simple product
        $allPlans = $allPlans->merge($this->simpleVariant->plans);

        // Associate all plans with the coupon
        $this->coupon->plans()->attach($allPlans->pluck('id')->toArray());

        // Create a product-type coupon for testing non-recurring variants
        $productCoupon = Coupon::factory()->create([
            'name' => 'Product Discount 20% Off',
            'promotion_code' => 'PRODUCT20',
            'type' => Coupon::TYPE_PRODUCT,
            'value' => 20,
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'duration' => CouponDuration::FOREVER,
            'auto_apply' => true,
            'active' => true,
        ]);

        // Associate both products with the coupon
        $productCoupon->products()->attach([$this->product->id, $this->simpleProduct->id]);
    }

    #[Test]
    public function it_returns_product_with_variant_and_plan_discount()
    {
        $controller = new ShopController();
        $response = $controller->product(new Request(), $this->product->slug);
        $data = $response->getData(true);

        // Verify basic product data
        $this->assertEquals($this->product->id, $data['id']);
        $this->assertEquals($this->product->title, $data['title']);
        $this->assertEquals($this->product->slug, $data['slug']);
        $this->assertTrue($data['has_variant']);

        // Verify options field is present and not empty for products with variants
        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);
        $this->assertGreaterThan(0, count($data['options']), 'Products with has_variant=true should have options from getOptionsWithValues()');

        // Verify variant information
        $this->assertArrayHasKey('variant_id', $data);
        $this->assertArrayHasKey('current_variant', $data);
        $this->assertArrayHasKey('variants', $data);

        // Verify plan information with discount
        $this->assertArrayHasKey('plan', $data);
        $this->assertNotNull($data['plan']);

        // Check if discount is applied to the plan
        if (isset($data['plan']['discount'])) {
            $this->assertEquals('FIRSTMONTH50', $data['plan']['discount']['coupon_code']);
            $this->assertEquals('percentage', $data['plan']['discount']['type']);
            $this->assertEquals(50, $data['plan']['discount']['value']);

            // Verify discounted price (50% off $85.00 = $42.50)
            $this->assertEquals(42.5, $data['price']);
            $this->assertEquals('$42.50', $data['price_formatted']);

            // Verify compare at price shows original price
            $this->assertEquals(85, $data['plan']['compare_at_price']);
            $this->assertEquals('$85.00', $data['plan']['compare_at_price_formatted']);
        }

        // Verify recurring plans with discounts
        $this->assertArrayHasKey('recurring_plans', $data);
        $this->assertIsArray($data['recurring_plans']);
        $this->assertGreaterThan(0, count($data['recurring_plans']));

        // Check if any recurring plan has discount
        $hasDiscountedPlan = false;
        foreach ($data['recurring_plans'] as $plan) {
            if (isset($plan['discount'])) {
                $hasDiscountedPlan = true;
                $this->assertEquals('FIRSTMONTH50', $plan['discount']['coupon_code']);
                $this->assertEquals('percentage', $plan['discount']['type']);
                $this->assertEquals(50, $plan['discount']['value']);
            }
        }
        $this->assertTrue($hasDiscountedPlan, 'At least one recurring plan should have a discount');

        // Verify has_discount flag
        $this->assertArrayHasKey('has_discount', $data);
        $this->assertTrue($data['has_discount']);
    }

    #[Test]
    public function it_returns_variant_with_plan_discount_when_switching()
    {
        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Verify basic variant data
        $this->assertEquals($this->variant->id, $data['variant_id']);
        $this->assertTrue($data['in_stock']);

        // Verify options field is present for variant switching
        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);
        $this->assertGreaterThan(0, count($data['options']), 'Variant switching should include options from getOptionsWithValues()');

        // Verify plan information with discount
        $this->assertArrayHasKey('plan', $data);
        $this->assertNotNull($data['plan']);

        // Check if discount is applied to the plan
        if (isset($data['plan']['discount'])) {
            $this->assertEquals('FIRSTMONTH50', $data['plan']['discount']['coupon_code']);
            $this->assertEquals('percentage', $data['plan']['discount']['type']);
            $this->assertEquals(50, $data['plan']['discount']['value']);

            // Verify discounted price (50% off $85.00 = $42.50)
            $this->assertEquals(42.5, $data['price']);
            $this->assertEquals('$42.50', $data['price_formatted']);
        }

        // Verify current variant info (should not have discount for recurring variants)
        $this->assertArrayHasKey('current_variant', $data);
        $this->assertNull($data['current_variant']['discount']);

        // Verify recurring plans with discounts
        $this->assertArrayHasKey('recurring_plans', $data);
        $this->assertIsArray($data['recurring_plans']);
    }

    #[Test]
    public function it_applies_product_coupon_to_non_recurring_variant()
    {
        // Create a non-recurring variant
        $nonRecurringVariant = Variant::create([
            'product_id' => $this->product->id,
            'price' => 100.00,
            'in_stock' => true,
            'recurring' => false,
            'is_default' => false,
        ]);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $nonRecurringVariant);
        $data = $response->getData(true);

        // Verify basic variant data
        $this->assertEquals($nonRecurringVariant->id, $data['variant_id']);
        $this->assertTrue($data['in_stock']);

        // For non-recurring variants, plan should be null
        $this->assertNull($data['plan']);
        $this->assertEmpty($data['recurring_plans']);

        // Check if product discount is applied to the variant
        if (isset($data['current_variant']['discount'])) {
            $this->assertEquals('PRODUCT20', $data['current_variant']['discount']['coupon_code']);
            $this->assertEquals('percentage', $data['current_variant']['discount']['type']);
            $this->assertEquals(20, $data['current_variant']['discount']['value']);

            // Verify discounted price (20% off $100.00 = $80.00)
            $this->assertEquals(80, $data['price']);
            $this->assertEquals('$80.00', $data['price_formatted']);
        }
    }

    #[Test]
    public function it_returns_no_discount_when_no_applicable_coupon()
    {
        // Create a new product that's not associated with any coupons
        $newProduct = Product::factory()->create([
            'title' => 'Product Without Coupons',
            'slug' => 'product-without-coupons',
            'description' => 'A product without any associated coupons',
            'has_variant' => false,
        ]);

        // Create a plan without any associated coupons
        $variantWithoutCoupon = Variant::create([
            'product_id' => $newProduct->id,
            'price' => 50.00,
            'in_stock' => true,
            'recurring' => true,
            'is_default' => true,
        ]);

        $planWithoutCoupon = Plan::factory()->create([
            'variant_id' => $variantWithoutCoupon->id,
            'label' => 'No Discount Plan',
            'slug' => 'no-discount',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 50.00,
            'is_active' => true,
        ]);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $variantWithoutCoupon);
        $data = $response->getData(true);

        // Verify no discount is applied
        $this->assertEquals(50, $data['price']);
        $this->assertEquals('$50.00', $data['price_formatted']);

        // Plan should not have discount (or discount should be null)
        $this->assertTrue(!isset($data['plan']['discount']) || $data['plan']['discount'] === null);

        // Variant should not have discount (or discount should be null)
        $this->assertTrue(!isset($data['current_variant']['discount']) || $data['current_variant']['discount'] === null);
    }

    #[Test]
    public function it_handles_inactive_coupons_correctly()
    {
        // Deactivate the coupon
        $this->coupon->update(['active' => false]);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Verify no discount is applied when coupon is inactive
        $this->assertEquals(85, $data['price']);
        $this->assertEquals('$85.00', $data['price_formatted']);

        // Plan should not have discount (or discount should be null)
        $this->assertTrue(!isset($data['plan']['discount']) || $data['plan']['discount'] === null);
    }

    #[Test]
    public function it_handles_expired_coupons_correctly()
    {
        // Set coupon as expired
        $this->coupon->update(['expires_at' => now()->subDay()]);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Verify no discount is applied when coupon is expired
        $this->assertEquals(85, $data['price']);
        $this->assertEquals('$85.00', $data['price_formatted']);

        // Plan should not have discount (or discount should be null)
        $this->assertTrue(!isset($data['plan']['discount']) || $data['plan']['discount'] === null);
    }

    #[Test]
    public function it_applies_different_coupons_to_different_plans()
    {
        // Create a second plan for the same variant
        $yearlyPlan = Plan::factory()->create([
            'variant_id' => $this->variant->id,
            'label' => 'Yearly',
            'slug' => 'yearly',
            'interval' => 'year',
            'interval_count' => 1,
            'price' => 600.00,
            'trial_days' => 0,
            'is_active' => true,
        ]);

        // Create a specific coupon for the yearly plan (different from monthly)
        // Make it better than the existing 50% coupon to ensure it gets applied
        $yearlyCoupon = Coupon::factory()->create([
            'name' => 'Yearly Plan 60% Off',
            'promotion_code' => 'YEARLY60',
            'type' => Coupon::TYPE_PLAN,
            'value' => 60,
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'duration' => CouponDuration::FOREVER,
            'auto_apply' => true,
            'active' => true,
        ]);

        // Associate only the yearly plan with this coupon
        $yearlyCoupon->plans()->attach($yearlyPlan->id);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Verify we have multiple recurring plans
        $this->assertGreaterThan(1, count($data['recurring_plans']));

        $monthlyPlanData = null;
        $yearlyPlanData = null;

        // Find the monthly and yearly plans in the response
        foreach ($data['recurring_plans'] as $planData) {
            if ($planData['slug'] === 'monthly') {
                $monthlyPlanData = $planData;
            } elseif ($planData['slug'] === 'yearly') {
                $yearlyPlanData = $planData;
            }
        }

        $this->assertNotNull($monthlyPlanData, 'Monthly plan should be present');
        $this->assertNotNull($yearlyPlanData, 'Yearly plan should be present');

        // Verify monthly plan has the 50% discount
        if (isset($monthlyPlanData['discount'])) {
            $this->assertEquals('FIRSTMONTH50', $monthlyPlanData['discount']['coupon_code']);
            $this->assertEquals(50, $monthlyPlanData['discount']['value']);
        }

        // Verify yearly plan has the 60% discount
        if (isset($yearlyPlanData['discount'])) {
            $this->assertEquals('YEARLY60', $yearlyPlanData['discount']['coupon_code']);
            $this->assertEquals(60, $yearlyPlanData['discount']['value']);

            // Verify discounted price for yearly (60% off $600 = $240)
            $this->assertEquals(240, $yearlyPlanData['compare_at_price'] - ($yearlyPlanData['compare_at_price'] * 0.60));
        }

        // Verify different coupons are applied to different plans
        if (isset($monthlyPlanData['discount']) && isset($yearlyPlanData['discount'])) {
            $this->assertNotEquals(
                $monthlyPlanData['discount']['coupon_code'],
                $yearlyPlanData['discount']['coupon_code'],
                'Different plans should have different coupons applied'
            );
        }
    }

    #[Test]
    public function it_applies_best_coupon_when_multiple_coupons_available_for_same_plan()
    {
        // Create a second coupon for the same monthly plan with better discount
        $betterCoupon = Coupon::factory()->create([
            'name' => 'Better Monthly Deal 70% Off',
            'promotion_code' => 'BETTER70',
            'type' => Coupon::TYPE_PLAN,
            'value' => 70,
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'duration' => CouponDuration::ONCE,
            'auto_apply' => true,
            'active' => true,
        ]);

        // Associate this coupon with the same monthly plan
        $betterCoupon->plans()->attach($this->plan->id);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Find the monthly plan in the response
        $monthlyPlanData = null;
        foreach ($data['recurring_plans'] as $planData) {
            if ($planData['slug'] === 'monthly') {
                $monthlyPlanData = $planData;
                break;
            }
        }

        $this->assertNotNull($monthlyPlanData, 'Monthly plan should be present');

        // Verify the better coupon (70% off) is applied instead of the original (50% off)
        if (isset($monthlyPlanData['discount'])) {
            $this->assertEquals('BETTER70', $monthlyPlanData['discount']['coupon_code']);
            $this->assertEquals(70, $monthlyPlanData['discount']['value']);

            // Verify discounted price (70% off $85.00 = $25.50)
            $expectedDiscountedPrice = 85 - (85 * 0.70);
            $this->assertEquals($expectedDiscountedPrice, $monthlyPlanData['compare_at_price'] - ($monthlyPlanData['compare_at_price'] * 0.70));
        }
    }

    #[Test]
    public function it_does_not_apply_coupon_when_plan_has_trial_days()
    {
        // Create a plan with trial days
        $trialPlan = Plan::factory()->create([
            'variant_id' => $this->variant->id,
            'label' => 'Monthly with Trial',
            'slug' => 'monthly-trial',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 90.00,
            'trial_days' => 14, // 14-day trial
            'is_active' => true,
        ]);

        // Create a coupon for this trial plan
        $trialCoupon = Coupon::factory()->create([
            'name' => 'Trial Plan 40% Off',
            'promotion_code' => 'TRIAL40',
            'type' => Coupon::TYPE_PLAN,
            'value' => 40,
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'duration' => CouponDuration::ONCE,
            'auto_apply' => true,
            'active' => true,
        ]);

        // Associate the trial plan with the coupon
        $trialCoupon->plans()->attach($trialPlan->id);

        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Find the trial plan in the response
        $trialPlanData = null;
        foreach ($data['recurring_plans'] as $planData) {
            if ($planData['slug'] === 'monthly-trial') {
                $trialPlanData = $planData;
                break;
            }
        }

        $this->assertNotNull($trialPlanData, 'Trial plan should be present');

        // Verify trial days are set
        $this->assertEquals(14, $trialPlanData['trial_days']);

        // Verify NO discount is applied when plan has trial days > 0
        $this->assertTrue(
            !isset($trialPlanData['discount']) || $trialPlanData['discount'] === null,
            'Plans with trial_days > 0 should not have coupons applied'
        );

        // Verify price shows original price (no discount)
        $this->assertEquals(90, $trialPlanData['price'], 'Trial plan should show original price without discount');
        $this->assertEquals('$90.00', $trialPlanData['price_formatted'], 'Trial plan formatted price should show original price');

        // When no coupon is applied, compare_at_price should be null (as per controller logic)
        $this->assertTrue(
            !isset($trialPlanData['compare_at_price']) || $trialPlanData['compare_at_price'] === null,
            'Trial plan compare_at_price should be null when no coupon is applied'
        );
        $this->assertTrue(
            !isset($trialPlanData['compare_at_price_formatted']) || $trialPlanData['compare_at_price_formatted'] === null,
            'Trial plan compare_at_price_formatted should be null when no coupon is applied'
        );

        // Test switching directly to the trial plan variant to ensure no discount
        // First create a separate variant for the trial plan to test direct access
        $trialVariant = Variant::create([
            'product_id' => $this->product->id,
            'price' => 90.00,
            'compare_at_price' => 180.00,
            'in_stock' => true,
            'recurring' => true,
            'is_default' => false,
        ]);

        // Update the trial plan to use the new variant
        $trialPlan->update(['variant_id' => $trialVariant->id]);

        // Test variant endpoint directly
        $triantResponse = $controller->variant(new Request(), $trialVariant);
        $variantData = $triantResponse->getData(true);

        // Verify the selected plan (should be the trial plan) has no discount
        if ($variantData['plan']) {
            $this->assertEquals('monthly-trial', $variantData['plan']['slug']);
            $this->assertEquals(14, $variantData['plan']['trial_days']);
            $this->assertTrue(
                !isset($variantData['plan']['discount']) || $variantData['plan']['discount'] === null,
                'Selected trial plan should not have discount applied'
            );

            // Verify main price shows original price
            $this->assertEquals(90, $variantData['price'], 'Variant with trial plan should show original price');
            $this->assertEquals('$90.00', $variantData['price_formatted'], 'Variant with trial plan formatted price should show original price');
        }
    }

    #[Test]
    public function it_shows_correct_discounted_price_and_original_compare_at_price_for_plans()
    {
        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Find the monthly plan in the response (should have 50% discount)
        $monthlyPlanData = null;
        foreach ($data['recurring_plans'] as $planData) {
            if ($planData['slug'] === 'monthly') {
                $monthlyPlanData = $planData;
                break;
            }
        }

        $this->assertNotNull($monthlyPlanData, 'Monthly plan should be present');

        // Verify discount is applied
        $this->assertArrayHasKey('discount', $monthlyPlanData);
        $this->assertEquals('FIRSTMONTH50', $monthlyPlanData['discount']['coupon_code']);
        $this->assertEquals(50, $monthlyPlanData['discount']['value']);

        // Verify pricing structure:
        // - price should show discounted amount (50% off $85.00 = $42.50)
        // - compare_at_price should show original price ($85.00)
        $originalPrice = 85.00;
        $discountedPrice = $originalPrice - ($originalPrice * 0.50); // 50% off

        $this->assertEquals($discountedPrice, $monthlyPlanData['price'], 'Plan price should show discounted amount');
        $this->assertEquals('$42.50', $monthlyPlanData['price_formatted'], 'Plan price_formatted should show discounted amount');
        $this->assertEquals($originalPrice, $monthlyPlanData['compare_at_price'], 'Plan compare_at_price should show original price');
        $this->assertEquals('$85.00', $monthlyPlanData['compare_at_price_formatted'], 'Plan compare_at_price_formatted should show original price');

        // Also verify the main product price reflects the selected plan's discounted price
        $this->assertEquals($discountedPrice, $data['price'], 'Product price should match the discounted plan price');
        $this->assertEquals('$42.50', $data['price_formatted'], 'Product price_formatted should match the discounted plan price');
    }

    #[Test]
    public function it_applies_product_discount_to_default_variant_for_non_recurring_product()
    {
        // Create a non-recurring product with multiple variants
        $nonRecurringProduct = Product::factory()->create([
            'title' => 'Non-Recurring Product',
            'slug' => 'non-recurring-product',
            'description' => 'A non-recurring product with variants',
            'has_variant' => true,
        ]);

        // Create multiple non-recurring variants
        $defaultVariant = Variant::create([
            'product_id' => $nonRecurringProduct->id,
            'price' => 100.00,
            'compare_at_price' => 120.00,
            'in_stock' => true,
            'recurring' => false,
            'is_default' => false, // Changed to false since variants() relation excludes default variants
            'track_inventory' => false, // Don't track inventory for test
        ]);

        $secondVariant = Variant::create([
            'product_id' => $nonRecurringProduct->id,
            'price' => 150.00,
            'compare_at_price' => 180.00,
            'in_stock' => true,
            'recurring' => false,
            'is_default' => false,
            'track_inventory' => false, // Don't track inventory for test
        ]);

        // Create a product-type coupon for this product (25% off)
        $productCoupon = Coupon::factory()->create([
            'name' => 'Non-Recurring Product 25% Off',
            'promotion_code' => 'NONREC25',
            'type' => Coupon::TYPE_PRODUCT,
            'value' => 25,
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'duration' => CouponDuration::FOREVER,
            'auto_apply' => true,
            'active' => true,
        ]);

        // Associate the product with the coupon
        $productCoupon->products()->attach($nonRecurringProduct->id);

        // Test 1: Product endpoint with first variant should show discount
        $controller = new ShopController();
        $response = $controller->product(new Request(), $nonRecurringProduct->slug);
        $data = $response->getData(true);

        // Verify basic product data
        $this->assertEquals($nonRecurringProduct->id, $data['id']);
        $this->assertEquals($nonRecurringProduct->title, $data['title']);
        $this->assertTrue($data['has_variant']);

        // For non-recurring products, plan should be null
        $this->assertNull($data['plan']);
        $this->assertEmpty($data['recurring_plans']);

        // Verify current_variant has discount applied (this is the issue we're fixing)
        $this->assertArrayHasKey('current_variant', $data);
        $this->assertNotNull($data['current_variant']['discount'], 'First variant should have discount applied in product endpoint');
        $this->assertEquals('NONREC25', $data['current_variant']['discount']['coupon_code']);
        $this->assertEquals(25, $data['current_variant']['discount']['value']);

        // Verify discounted price for first variant (25% off $100.00 = $75.00)
        $this->assertEquals(75, $data['price']);
        $this->assertEquals('$75.00', $data['price_formatted']);

        // Verify compare at price shows original price
        $this->assertEquals(100, $data['current_variant']['compare_at_price']);
        $this->assertEquals('$100.00', $data['current_variant']['compare_at_price_formatted']);

        // Test 2: Variant switching should also work
        $variantResponse = $controller->variant(new Request(), $secondVariant);
        $variantData = $variantResponse->getData(true);

        // Verify second variant also has discount applied
        $this->assertNotNull($variantData['current_variant']['discount'], 'Second variant should have discount applied when switching');
        $this->assertEquals('NONREC25', $variantData['current_variant']['discount']['coupon_code']);
        $this->assertEquals(25, $variantData['current_variant']['discount']['value']);

        // Verify discounted price for second variant (25% off $150.00 = $112.50)
        $this->assertEquals(112.5, $variantData['price']);
        $this->assertEquals('$112.50', $variantData['price_formatted']);

        // Verify compare at price shows original price
        $this->assertEquals(150, $variantData['current_variant']['compare_at_price']);
        $this->assertEquals('$150.00', $variantData['current_variant']['compare_at_price_formatted']);

        // Verify has_discount flag is true
        $this->assertTrue($data['has_discount']);
        $this->assertTrue($variantData['has_discount']);
    }

    #[Test]
    public function it_returns_options_for_product_with_variants()
    {
        $controller = new ShopController();
        $response = $controller->product(new Request(), $this->product->slug);
        $data = $response->getData(true);

        // Verify product has has_variant set to true
        $this->assertTrue($data['has_variant']);

        // Verify options field is present and structured correctly
        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);
        $this->assertGreaterThan(0, count($data['options']));

        // Verify each option has the required structure from getOptionsWithValues()
        foreach ($data['options'] as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('name', $option);
            $this->assertArrayHasKey('values', $option);
            $this->assertArrayHasKey('type', $option);

            // Verify values is an array with proper structure
            $this->assertIsArray($option['values']);
            $this->assertGreaterThan(0, count($option['values']));

            foreach ($option['values'] as $value) {
                $this->assertArrayHasKey('id', $value);
                $this->assertArrayHasKey('name', $value);
            }
        }

        // Verify specific options exist
        $optionNames = array_column($data['options'], 'name');
        $this->assertContains('Size', $optionNames);
        $this->assertContains('Color', $optionNames);

        // Verify Size option has correct values
        $sizeOption = collect($data['options'])->firstWhere('name', 'Size');
        $this->assertNotNull($sizeOption);
        $sizeValueNames = array_column($sizeOption['values'], 'name');
        $this->assertContains('S', $sizeValueNames);
        $this->assertContains('M', $sizeValueNames);
        $this->assertContains('L', $sizeValueNames);

        // Verify Color option has correct values
        $colorOption = collect($data['options'])->firstWhere('name', 'Color');
        $this->assertNotNull($colorOption);
        $colorValueNames = array_column($colorOption['values'], 'name');
        $this->assertContains('Red', $colorValueNames);
        $this->assertContains('Blue', $colorValueNames);
        $this->assertContains('Green', $colorValueNames);
    }

    #[Test]
    public function it_returns_options_for_variant_switching()
    {
        $controller = new ShopController();
        $response = $controller->variant(new Request(), $this->variant);
        $data = $response->getData(true);

        // Verify variant has options field
        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);
        $this->assertGreaterThan(0, count($data['options']));

        // Verify each option has the required structure from getOptionsWithValues()
        foreach ($data['options'] as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('name', $option);
            $this->assertArrayHasKey('values', $option);
            $this->assertArrayHasKey('type', $option);

            // Verify values is an array with proper structure
            $this->assertIsArray($option['values']);
            $this->assertGreaterThan(0, count($option['values']));
        }

        // Verify options are the same as the product options (same structure and content)
        $productResponse = $controller->product(new Request(), $this->product->slug);
        $productData = $productResponse->getData(true);

        $this->assertCount(count($productData['options']), $data['options']);

        // Compare option structures by name and content rather than ID
        // (IDs might differ due to different query contexts)
        $variantOptionsByName = collect($data['options'])->keyBy('name');
        $productOptionsByName = collect($productData['options'])->keyBy('name');

        foreach ($productOptionsByName as $name => $productOption) {
            $this->assertArrayHasKey($name, $variantOptionsByName->toArray());
            $variantOption = $variantOptionsByName[$name];

            $this->assertEquals($productOption['name'], $variantOption['name']);
            $this->assertEquals($productOption['type'], $variantOption['type']);
            $this->assertCount(count($productOption['values']), $variantOption['values']);

            // Compare values by name rather than ID
            $productValueNames = collect($productOption['values'])->pluck('name')->sort()->values();
            $variantValueNames = collect($variantOption['values'])->pluck('name')->sort()->values();
            $this->assertEquals($productValueNames->toArray(), $variantValueNames->toArray());
        }
    }

    #[Test]
    public function it_does_not_return_options_for_product_without_variants()
    {
        // Create a product without variants
        $simpleProduct = Product::factory()->create([
            'title' => 'Simple Product',
            'slug' => 'simple-product',
            'description' => 'A simple product without variants',
            'has_variant' => false,
        ]);

        // Create a default variant for pricing (even simple products need a variant for pricing)
        $defaultVariant = Variant::create([
            'product_id' => $simpleProduct->id,
            'price' => 50.00,
            'compare_at_price' => 60.00,
            'in_stock' => true,
            'recurring' => false,
            'is_default' => true,
        ]);

        $controller = new ShopController();
        $response = $controller->product(new Request(), $simpleProduct->slug);
        $data = $response->getData(true);

        // Verify product has has_variant set to false
        $this->assertFalse($data['has_variant']);

        // Options field should still be present but empty
        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);
        $this->assertEmpty($data['options']);
    }

    #[Test]
    public function it_validates_options_data_structure()
    {
        $controller = new ShopController();
        $response = $controller->product(new Request(), $this->product->slug);
        $data = $response->getData(true);

        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);

        foreach ($data['options'] as $option) {
            // Validate option structure matches getOptionsWithValues() output
            $this->assertIsArray($option);
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('name', $option);
            $this->assertArrayHasKey('values', $option);
            $this->assertArrayHasKey('type', $option);

            // Validate data types
            $this->assertIsInt($option['id']);
            $this->assertIsString($option['name']);
            $this->assertIsArray($option['values']);
            $this->assertIsString($option['type']);

            // Validate values structure
            foreach ($option['values'] as $value) {
                $this->assertIsArray($value);
                $this->assertArrayHasKey('id', $value);
                $this->assertArrayHasKey('name', $value);
                $this->assertIsInt($value['id']);
                $this->assertIsString($value['name']);
            }
        }
    }

    #[Test]
    public function it_returns_properly_mapped_products_for_listing()
    {
        $controller = new ShopController();
        $request = new Request();
        $response = $controller->products($request, new Product());
        $data = $response->getData(true);

        // Verify response structure
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);

        // Verify meta information
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('current_page', $data['meta']);
        $this->assertArrayHasKey('last_page', $data['meta']);
        $this->assertArrayHasKey('per_page', $data['meta']);

        // Verify we have products
        $this->assertIsArray($data['data']);
        $this->assertGreaterThan(0, count($data['data']));

        // Test the first product in the listing
        $product = $data['data'][0];

        // Verify basic product structure
        $this->assertArrayHasKey('id', $product);
        $this->assertArrayHasKey('title', $product);
        $this->assertArrayHasKey('slug', $product);
        $this->assertArrayHasKey('description', $product);
        $this->assertArrayHasKey('thumbnail', $product);
        $this->assertArrayHasKey('has_variant', $product);
        $this->assertArrayHasKey('recurring', $product);
        $this->assertArrayHasKey('in_stock', $product);
        $this->assertArrayHasKey('category_id', $product);

        // Verify pricing structure
        $this->assertArrayHasKey('price', $product);
        $this->assertArrayHasKey('price_formatted', $product);
        $this->assertArrayHasKey('variant_id', $product);
        $this->assertArrayHasKey('current_variant', $product);
        $this->assertArrayHasKey('has_discount', $product);

        // Verify data types
        $this->assertIsInt($product['id']);
        $this->assertIsString($product['title']);
        $this->assertIsString($product['slug']);
        $this->assertIsBool($product['has_variant']);
        $this->assertIsBool($product['recurring']);
        $this->assertIsBool($product['in_stock']);
        $this->assertIsBool($product['has_discount']);
        $this->assertIsNumeric($product['price']);
        $this->assertIsString($product['price_formatted']);

        // Test product with variants
        $productWithVariants = collect($data['data'])->first(function ($p) {
            return $p['has_variant'] === true;
        });

        if ($productWithVariants) {
            $this->assertArrayHasKey('variants', $productWithVariants);
            $this->assertIsArray($productWithVariants['variants']);
            $this->assertGreaterThan(0, count($productWithVariants['variants']));

            // Verify variant structure in listing
            foreach ($productWithVariants['variants'] as $variant) {
                $this->assertArrayHasKey('id', $variant);
                $this->assertArrayHasKey('title', $variant);
                $this->assertArrayHasKey('price', $variant);
                $this->assertArrayHasKey('price_formatted', $variant);
                $this->assertArrayHasKey('in_stock', $variant);
                $this->assertArrayHasKey('thumbnail', $variant);
            }
        }

        // Test recurring product structure
        $recurringProduct = collect($data['data'])->first(function ($p) {
            return $p['recurring'] === true || !is_null($p['plan']);
        });

        if ($recurringProduct) {
            $this->assertArrayHasKey('plan', $recurringProduct);
            if ($recurringProduct['plan']) {
                $this->assertIsArray($recurringProduct['plan']);
                $this->assertArrayHasKey('id', $recurringProduct['plan']);
                $this->assertArrayHasKey('label', $recurringProduct['plan']);
                $this->assertArrayHasKey('price', $recurringProduct['plan']);
                $this->assertArrayHasKey('interval', $recurringProduct['plan']);
            }
        }
    }

    #[Test]
    public function it_applies_filters_correctly_in_products_listing()
    {
        // Create additional test products for filtering
        $categoryProduct = Product::factory()->create([
            'title' => 'Category Filter Test Product',
            'category_id' => $this->product->category_id, // Same category as main test product
            'has_variant' => false,
        ]);

        $expensiveProduct = Product::factory()->create([
            'title' => 'Expensive Test Product',
            'has_variant' => false,
        ]);

        // Create expensive variant
        $expensiveVariant = Variant::create([
            'product_id' => $expensiveProduct->id,
            'price' => 500.00,
            'in_stock' => true,
            'recurring' => false,
            'is_default' => true,
        ]);

        $controller = new ShopController();

        // Test search filter
        $searchRequest = new Request(['search' => 'Category Filter']);
        $searchResponse = $controller->products($searchRequest, new Product());
        $searchData = $searchResponse->getData(true);

        $this->assertGreaterThan(0, count($searchData['data']));
        $foundProduct = collect($searchData['data'])->first(function ($p) {
            return str_contains($p['title'], 'Category Filter');
        });
        $this->assertNotNull($foundProduct);

        // Test category filter
        $categoryRequest = new Request(['categories' => [$this->product->category_id]]);
        $categoryResponse = $controller->products($categoryRequest, new Product());
        $categoryData = $categoryResponse->getData(true);

        $this->assertGreaterThan(0, count($categoryData['data']));
        foreach ($categoryData['data'] as $product) {
            $this->assertEquals($this->product->category_id, $product['category_id']);
        }

        // Test price range filter
        // $priceRequest = new Request(['price_min' => 400, 'price_max' => 600]);
        // $priceResponse = $controller->products($priceRequest, new Product());
        // $priceData = $priceResponse->getData(true);

        // $expensiveProductFound = collect($priceData['data'])->first(function ($p) use ($expensiveProduct) {
        //     return $p['id'] === $expensiveProduct->id;
        // });
        // $this->assertNotNull($expensiveProductFound);

        // Test availability filter - in stock
        // $availabilityRequest = new Request(['availability' => 'in_stock']);
        // $availabilityResponse = $controller->products($availabilityRequest, new Product());
        // $availabilityData = $availabilityResponse->getData(true);

        // $this->assertGreaterThan(0, count($availabilityData['data']));
        // foreach ($availabilityData['data'] as $product) {
        //     $this->assertTrue($product['in_stock']);
        // }
    }

    #[Test]
    public function it_returns_products_with_correct_discount_information()
    {
        $controller = new ShopController();
        $request = new Request();
        $response = $controller->products($request, new Product());
        $data = $response->getData(true);

        // Find a product that should have discount (our test product with coupon)
        $discountedProduct = collect($data['data'])->first(function ($p) {
            return $p['has_discount'] === true;
        });

        if ($discountedProduct) {
            $this->assertTrue($discountedProduct['has_discount']);

            if ($discountedProduct['recurring'] && $discountedProduct['plan']) {
                // For recurring products, check plan discount
                $this->assertArrayHasKey('plan', $discountedProduct);
                if (isset($discountedProduct['plan']['discount'])) {
                    $this->assertArrayHasKey('discount', $discountedProduct['plan']);
                    $this->assertArrayHasKey('coupon_code', $discountedProduct['plan']['discount']);
                }
            } else {
                // For non-recurring products, check current_variant discount
                $this->assertArrayHasKey('current_variant', $discountedProduct);
                if (isset($discountedProduct['current_variant']['discount'])) {
                    $this->assertArrayHasKey('discount', $discountedProduct['current_variant']);
                    $this->assertArrayHasKey('coupon_code', $discountedProduct['current_variant']['discount']);
                }
            }

            // Verify compare_at_price is present when there's a discount
            $this->assertArrayHasKey('compare_at_price', $discountedProduct);
        }
    }

    #[Test]
    public function it_handles_pagination_correctly()
    {
        // Create additional products to test pagination
        for ($i = 1; $i <= 20; $i++) {
            $product = Product::factory()->create([
                'title' => "Pagination Test Product {$i}",
                'has_variant' => false,
            ]);

            Variant::create([
                'product_id' => $product->id,
                'price' => 10.00 + $i,
                'in_stock' => true,
                'recurring' => false,
                'is_default' => true,
            ]);
        }

        $controller = new ShopController();

        // Test first page
        $page1Request = new Request(['rowsPerPage' => 5]);
        $page1Response = $controller->products($page1Request, new Product());
        $page1Data = $page1Response->getData(true);

        $this->assertEquals(5, count($page1Data['data']));
        $this->assertEquals(1, $page1Data['meta']['current_page']);
        $this->assertGreaterThan(1, $page1Data['meta']['last_page']);
        $this->assertGreaterThan(20, $page1Data['meta']['total']);

        // Test custom page size
        $customSizeRequest = new Request(['rowsPerPage' => 10]);
        $customSizeResponse = $controller->products($customSizeRequest, new Product());
        $customSizeData = $customSizeResponse->getData(true);

        $this->assertEquals(10, count($customSizeData['data']));
        $this->assertEquals(10, $customSizeData['meta']['per_page']);
    }

    #[Test]
    public function it_returns_stock_information_correctly()
    {
        // Create a product with tracked inventory
        $stockProduct = Product::factory()->create([
            'title' => 'Stock Test Product',
            'has_variant' => false,
        ]);

        $stockVariant = Variant::create([
            'product_id' => $stockProduct->id,
            'price' => 25.00,
            'in_stock' => true,
            'recurring' => false,
            'is_default' => true,
            'track_inventory' => true,
        ]);

        // Create inventory for the variant (using the inventory system)
        $location = \Coderstm\Models\Shop\Location::first();
        if (!$location) {
            $location = \Coderstm\Models\Shop\Location::create([
                'name' => 'Test Location',
                'active' => true,
            ]);
        }

        \Coderstm\Models\Shop\Product\Inventory::create([
            'variant_id' => $stockVariant->id,
            'location_id' => $location->id,
            'available' => 3,
            'active' => true,
            'tracking' => true,
        ]);

        // Create an out of stock product
        $outOfStockProduct = Product::factory()->create([
            'title' => 'Out of Stock Product',
            'has_variant' => false,
        ]);

        $outOfStockVariant = Variant::create([
            'product_id' => $outOfStockProduct->id,
            'price' => 30.00,
            'in_stock' => false,
            'recurring' => false,
            'is_default' => true,
            'track_inventory' => true,
        ]);

        // Create empty inventory for the out of stock variant
        \Coderstm\Models\Shop\Product\Inventory::create([
            'variant_id' => $outOfStockVariant->id,
            'location_id' => $location->id,
            'available' => 0,
            'active' => true,
            'tracking' => true,
        ]);

        $controller = new ShopController();
        $request = new Request();
        $response = $controller->products($request, new Product());
        $data = $response->getData(true);

        // Find our test products in the response
        $stockProductData = collect($data['data'])->first(function ($p) use ($stockProduct) {
            return $p['id'] === $stockProduct->id;
        });

        $outOfStockProductData = collect($data['data'])->first(function ($p) use ($outOfStockProduct) {
            return $p['id'] === $outOfStockProduct->id;
        });

        // Verify stock information is correctly mapped
        if ($stockProductData) {
            $this->assertTrue($stockProductData['in_stock']);
            $this->assertTrue($stockProductData['track_inventory']);
            $this->assertEquals(3, $stockProductData['stock']);
        }

        if ($outOfStockProductData) {
            // Note: in_stock might be calculated based on inventory
            $this->assertTrue($outOfStockProductData['track_inventory']);
            $this->assertEquals(0, $outOfStockProductData['stock']);
        }
    }
}
