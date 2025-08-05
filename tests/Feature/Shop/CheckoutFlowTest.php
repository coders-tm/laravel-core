<?php

namespace Tests\Feature\Shop;

use Coderstm\Models\Coupon;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Subscription\Plan;
use PHPUnit\Framework\Attributes\Test;
use Coderstm\Models\Shop\Product\Variant;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CheckoutController;
use Coderstm\Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    protected $product;
    protected $variant;
    protected $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test product and variant
        $this->product = Product::factory()->create([
            'title' => 'Test Product',
            'has_variant' => true
        ]);

        $this->variant = Variant::factory()->create([
            'product_id' => $this->product->id,
            'price' => 15.00,
            'sku' => 'TEST-VAR-001'
        ]);

        $this->coupon = Coupon::factory()->create([
            'promotion_code' => 'TEST10',
            'discount_type' => 'percentage',
            'value' => 10,
            'type' => Coupon::TYPE_PRODUCT,
            'active' => true
        ]);
    }

    #[Test]
    public function it_creates_empty_cart()
    {
        $request = new Request();
        $request->setLaravelSession(app('session')->driver());
        $controller = new CartController();

        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData();
        $this->assertIsArray($data);
        $this->assertCount(0, $data); // Empty cart
    }

    #[Test]
    public function it_adds_product_to_cart()
    {
        $request = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2
        ]);
        $request->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $response = $controller->add($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $response->getData(true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertCount(1, $responseData['data']); // One item in cart

        $item = $responseData['data'][0];
        $this->assertEquals($this->product->id, $item['product_id']);
        $this->assertEquals($this->variant->id, $item['variant_id']);
        $this->assertEquals(2, $item['quantity']);
    }

    #[Test]
    public function it_updates_existing_cart_item_quantity()
    {
        // First add an item
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1
        ]);
        $addRequest->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $controller->add($addRequest);

        // Then add the same item again
        $controller->add($addRequest);

        // Verify quantity is updated to 2
        $indexRequest = new Request();
        $indexRequest->setLaravelSession(app('session')->driver());
        $indexResponse = $controller->index($indexRequest);
        $data = $indexResponse->getData(true);

        $this->assertCount(1, $data); // Still one unique item
        $this->assertEquals(2, $data[0]['quantity']); // But quantity is now 2
    }

    #[Test]
    public function it_updates_cart_item_quantity()
    {
        // Add item first
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1
        ]);
        $addRequest->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $addResponse = $controller->add($addRequest);

        $addData = $addResponse->getData(true);
        $itemId = $addData['data'][0]['id'];

        // Update quantity
        $updateRequest = new Request(['quantity' => 5]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $controller->update($updateRequest, $itemId);

        $this->assertEquals(200, $updateResponse->getStatusCode());

        $updateData = $updateResponse->getData(true);

        $this->assertCount(1, $updateData['data']); // Still one item
        $this->assertEquals($itemId, $updateData['data'][0]['id']); // Same item ID

        $this->assertEquals(5, $updateData['data'][0]['quantity']);
    }

    #[Test]
    public function it_removes_item_from_cart()
    {
        // Add item first
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1
        ]);
        $addRequest->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $addResponse = $controller->add($addRequest);

        $addData = $addResponse->getData(true);
        $itemId = $addData['data'][0]['id'];

        // Remove item
        $removeRequest = new Request();
        $removeRequest->setLaravelSession(app('session')->driver());
        $removeResponse = $controller->remove($removeRequest, $itemId);

        $this->assertEquals(200, $removeResponse->getStatusCode());

        $removeData = $removeResponse->getData(true);
        $this->assertCount(0, $removeData['data']); // Cart should be empty
    }

    #[Test]
    public function it_clears_entire_cart()
    {
        // Add multiple items
        $controller = new CartController();

        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1
        ]);
        $addRequest->setLaravelSession(app('session')->driver());
        $controller->add($addRequest);

        // Clear cart
        $clearRequest = new Request();
        $clearRequest->setLaravelSession(app('session')->driver());
        $clearResponse = $controller->clear($clearRequest);

        $this->assertEquals(200, $clearResponse->getStatusCode());

        $clearData = $clearResponse->getData(true);

        $this->assertCount(0, $clearData['data']); // Cart should be empty
    }

    #[Test]
    public function it_calculates_cart_totals_correctly()
    {
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2 // 2 × $15.00 = $30.00
        ]);
        $addRequest->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $controller->add($addRequest);

        // Get checkout data
        $checkoutController = new CheckoutController();

        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);

        $this->assertEquals(30.00, $checkoutData['sub_total']);
        $this->assertArrayHasKey('tax_total', $checkoutData);
        $this->assertArrayHasKey('grand_total', $checkoutData);
    }

    #[Test]
    public function it_applies_coupon_to_checkout()
    {
        // Add item to cart
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2 // 2 × $15.00 = $30.00
        ]);
        $addRequest->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $controller->add($addRequest);

        // Create checkout
        $checkoutController = new CheckoutController();

        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Apply coupon
        $couponRequest = new Request(['coupon_code' => 'TEST10']);
        $couponResponse = $checkoutController->applyCoupon($couponRequest, $token);

        $this->assertEquals(200, $couponResponse->getStatusCode());

        $couponData = $couponResponse->getData(true);
        $this->assertEquals(30.00, $couponData['data']['sub_total']);
        $this->assertEquals(3.00, $couponData['data']['discount_total']); // 10% of $30
        $this->assertEquals('TEST10', $couponData['data']['coupon_code']);
    }

    #[Test]
    public function it_removes_coupon_from_checkout()
    {
        // Add item and apply coupon first
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2
        ]);
        $addRequest->setLaravelSession(app('session')->driver());

        $controller = new CartController();
        $controller->add($addRequest);

        $checkoutController = new CheckoutController();

        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Apply coupon
        $couponRequest = new Request(['coupon_code' => 'TEST10']);
        $checkoutController->applyCoupon($couponRequest, $token);

        // Remove coupon
        $removeResponse = $checkoutController->removeCoupon(new Request(), $token);

        $this->assertEquals(200, $removeResponse->getStatusCode());

        $removeData = $removeResponse->getData(true);
        $this->assertEquals(0.00, $removeData['discount_total']); // No discount
        $this->assertNull($removeData['coupon_code']); // No coupon code
    }

    #[Test]
    public function it_updates_checkout_customer_info()
    {
        // Create checkout first
        $checkoutController = new CheckoutController();

        $indexRequest = new Request();
        $indexRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($indexRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Update customer info
        $updateRequest = new Request([
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '+1234567890'
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());

        $updateResponse = $checkoutController->update($updateRequest, $token);

        $this->assertEquals(200, $updateResponse->getStatusCode());

        $updateData = $updateResponse->getData(true);
        $this->assertEquals('customer@example.com', $updateData['data']['email']);
        $this->assertEquals('John', $updateData['data']['first_name']);
        $this->assertEquals('Doe', $updateData['data']['last_name']);
        $this->assertEquals('+1234567890', $updateData['data']['phone_number']);
    }

    #[Test]
    public function it_shows_checkout_data_without_recalculation()
    {
        $checkoutController = new CheckoutController();

        $indexRequest = new Request();
        $indexRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($indexRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Show checkout (read-only)
        $showRequest = new Request();
        $showRequest->setLaravelSession(app('session')->driver());
        $showResponse = $checkoutController->show($showRequest, $token);

        $this->assertEquals(200, $showResponse->getStatusCode());

        $showData = $showResponse->getData(true);
        $this->assertEquals($token, $showData['token']);
        $this->assertArrayHasKey('sub_total', $showData);
        $this->assertArrayHasKey('grand_total', $showData);
    }

    #[Test]
    public function it_preserves_auto_applied_coupon_on_update()
    {
        // Cteate a coupon first so it can be auto-applied
        $coupon = Coupon::factory()->create([
            'promotion_code' => 'AUTO10',
            'discount_type' => 'percentage',
            'value' => 30,
            'auto_apply' => true,
            'type' => Coupon::TYPE_PRODUCT,
            'active' => true
        ]);

        // Add item to cart
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2
        ]);
        $addRequest->setLaravelSession(app('session')->driver());
        $controller = new CartController();
        $controller->add($addRequest);

        // Create checkout (auto-coupon may be applied in index)
        $checkoutController = new CheckoutController();
        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Confirm auto-applied coupon exists (if any)
        $autoCoupon = $checkoutData['coupon_code'] ?? null;
        $autoDiscount = $checkoutData['discount_total'] ?? 0;
        $appliedCoupon = $checkoutData['applied_coupons'] ?? null;

        $this->assertNotNull($autoCoupon);
        $this->assertGreaterThan(0, $autoDiscount);
        $this->assertNotEmpty($appliedCoupon);

        // Update checkout (should not remove auto-applied coupon)
        $updateRequest = new Request([
            'email' => 'auto@example.com',
            'first_name' => 'Auto',
            'last_name' => 'Coupon',
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $checkoutController->update($updateRequest, $token);
        $updateData = $updateResponse->getData(true);

        // Coupon code and discount should remain unchanged
        $this->assertEquals($autoCoupon, $updateData['data']['coupon_code']);
        $this->assertEquals($autoDiscount, $updateData['data']['discount_total']);
        $this->assertEquals($appliedCoupon, $updateData['data']['applied_coupons']);
        $this->assertEquals($token, $updateData['data']['token']);
    }

    #[Test]
    public function it_preserves_auto_applied_coupon_on_update_using_subscription_checkout()
    {
        // Cteate a coupon first so it can be auto-applied
        $coupon = Coupon::factory()->create([
            'type' => Coupon::TYPE_PLAN,
            'promotion_code' => 'FMONEUSD',
            'discount_type' => Coupon::DISCOUNT_TYPE_OVERRIDE,
            'value' => 1,
            'auto_apply' => true,
            'active' => true
        ]);

        $plan = Plan::factory()->create([
            'label' => 'Test Subscription Plan',
            'price' => 10.00,
            'interval' => 'month',
            'interval_count' => 1,
            'variant_id' => $this->variant->id,
            'is_active' => true,
            'trial_days' => 0,
        ]);

        // Create checkout (auto-coupon may be applied in index)
        $checkoutController = new CheckoutController();
        $checkoutRequest = new Request([
            'variant_id' => $this->variant->id,
            'plan_id' => $plan->id,
            'quantity' => 1
        ]);
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->subscription($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Confirm auto-applied coupon exists (if any)
        $autoCoupon = $checkoutData['coupon_code'] ?? null;
        $autoDiscount = $checkoutData['discount_total'] ?? 0;
        $appliedCoupon = $checkoutData['applied_coupons'] ?? null;

        $this->assertNotNull($autoCoupon);
        $this->assertGreaterThan(0, $autoDiscount);
        $this->assertNotEmpty($appliedCoupon);

        // Update checkout (should not remove auto-applied coupon)
        $updateRequest = new Request([
            'email' => 'auto@example.com',
            'first_name' => 'Auto',
            'last_name' => 'Coupon',
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $checkoutController->update($updateRequest, $token);
        $updateData = $updateResponse->getData(true);

        // Coupon code and discount should remain unchanged
        $this->assertEquals($autoCoupon, $updateData['data']['coupon_code']);
        $this->assertEquals($autoDiscount, $updateData['data']['discount_total']);
        $this->assertEquals($appliedCoupon, $updateData['data']['applied_coupons']);
        $this->assertEquals($token, $updateData['data']['token']);
    }

    #[Test]
    public function it_preserves_auto_applied_coupon_on_update_using_free_trial_plan()
    {
        $plan = Plan::factory()->create([
            'label' => 'Test Subscription Plan',
            'price' => 10.00,
            'interval' => 'month',
            'interval_count' => 1,
            'variant_id' => $this->variant->id,
            'is_active' => true,
            'trial_days' => 30,
        ]);

        // Create checkout (auto-coupon may be applied in index)
        $checkoutController = new CheckoutController();
        $checkoutRequest = new Request([
            'variant_id' => $this->variant->id,
            'plan_id' => $plan->id,
            'quantity' => 1
        ]);
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->subscription($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);
        $token = $checkoutData['token'];

        // Confirm auto-applied coupon exists (if any)
        $autoCoupon = $checkoutData['coupon_code'] ?? null;
        $autoDiscount = $checkoutData['discount_total'] ?? 0;
        $appliedCoupon = $checkoutData['applied_coupons'] ?? null;

        $this->assertNull($autoCoupon);
        $this->assertGreaterThan(0, $autoDiscount);
        $this->assertNotEmpty($appliedCoupon);

        // Update checkout (should not remove auto-applied coupon)
        $updateRequest = new Request([
            'email' => 'auto@example.com',
            'first_name' => 'Skip Free Trial',
            'last_name' => 'Coupon',
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $checkoutController->update($updateRequest, $token);
        $updateData = $updateResponse->getData(true);

        // Coupon code and discount should remain unchanged
        $this->assertEquals($autoCoupon, $updateData['data']['coupon_code']);
        $this->assertEquals($autoDiscount, $updateData['data']['discount_total']);
        $this->assertEquals($appliedCoupon, $updateData['data']['applied_coupons']);
        $this->assertEquals($token, $updateData['data']['token']);
    }
}
