<?php

namespace Tests\Feature\Shop;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Shop\Product\Variant;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\CheckoutController;
use Coderstm\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class ShopControllerTest extends FeatureTestCase
{
    use WithFaker;

    protected $product;
    protected $variant;

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
    }

    #[Test]
    public function it_calculates_order_totals_with_line_items()
    {
        $order = Order::factory()->create();
        $request = new Request([
            'id' => $order->id,
            'line_items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'price' => 15.00],
                ['product_id' => $this->product->id, 'quantity' => 1, 'price' => 10.00],
            ],
        ]);
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->calculator($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('line_items', $data);
        $this->assertCount(2, $data['line_items']);
    }

    #[Test]
    public function it_creates_checkout_with_cart_items_and_sets_up_stripe_payment_intent()
    {
        // Add items to cart first
        $cartController = new CartController();
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2 // 2 × $15.00 = $30.00
        ]);
        $addRequest->setLaravelSession(app('session')->driver());
        $cartController->add($addRequest);

        // Create checkout from cart
        $checkoutController = new CheckoutController();
        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);

        // Add customer information to checkout (required for Stripe)
        $updateRequest = new Request([
            'email' => 'test-customer@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $checkoutController->update($updateRequest, $checkoutData['token']);
        $updatedCheckoutData = $updateResponse->getData(true);

        // Verify checkout has proper data
        $this->assertEquals(30.00, $updatedCheckoutData['data']['sub_total']);
        $this->assertArrayHasKey('token', $updatedCheckoutData['data']);
        $this->assertArrayHasKey('line_items', $updatedCheckoutData['data']);
        $this->assertCount(1, $updatedCheckoutData['data']['line_items']);

        // Setup Stripe payment intent
        $shopController = new ShopController();
        $paymentRequest = new Request([
            'checkout_token' => $updatedCheckoutData['data']['token'],
            'amount' => $updatedCheckoutData['data']['grand_total'],
            'currency' => 'USD',
        ]);
        $paymentRequest->setLaravelSession(app('session')->driver());

        $response = $shopController->setupPaymentIntent($paymentRequest, 'stripe');

        // Debug the actual response if it fails
        if ($response->getStatusCode() !== 200) {
            $errorData = $response->getData(true);
            $this->fail('Setup payment intent failed with status ' . $response->getStatusCode() . ': ' . json_encode($errorData));
        }

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);

        // Stripe setupPaymentIntent returns client_secret and payment_intent_id
        $this->assertArrayHasKey('client_secret', $data);
        $this->assertArrayHasKey('payment_intent_id', $data);
        $this->assertNotEmpty($data['client_secret']);
        $this->assertNotEmpty($data['payment_intent_id']);
    }

    #[Test]
    public function it_confirms_stripe_payment_for_checkout_with_line_items()
    {
        // Add items to cart first
        $cartController = new CartController();
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1 // 1 × $15.00 = $15.00
        ]);
        $addRequest->setLaravelSession(app('session')->driver());
        $cartController->add($addRequest);

        // Create checkout from cart
        $checkoutController = new CheckoutController();
        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);

        // Add customer information to checkout (required for Stripe)
        $updateRequest = new Request([
            'email' => 'confirm-test@example.com',
            'first_name' => 'Confirm',
            'last_name' => 'Test',
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $checkoutController->update($updateRequest, $checkoutData['token']);
        $updatedCheckoutData = $updateResponse->getData(true);

        // Confirm payment with invalid payment intent ID (should fail as expected)
        $shopController = new ShopController();
        $confirmRequest = new Request([
            'checkout_token' => $updatedCheckoutData['data']['token'],
            'payment_intent_id' => 'pi_test_' . Str::random(24), // Mock payment intent ID
        ]);
        $confirmRequest->setLaravelSession(app('session')->driver());

        $response = $shopController->confirmPayment($confirmRequest, 'stripe');

        // This should fail with 500 because the payment intent doesn't exist
        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Payment confirmation failed', $data['error']);
        $this->assertStringContainsString('No such payment_intent', $data['message']);
    }

    #[Test]
    public function it_handles_stripe_checkout_success_redirect()
    {
        $request = new Request();
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->handleCheckoutSuccess($request, 'stripe');

        $this->assertTrue($response->isRedirect());
        $this->assertStringEndsWith('/user/shop/cart', $response->getTargetUrl());
    }

    #[Test]
    public function it_handles_stripe_checkout_cancel_redirect()
    {
        $request = new Request();
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->handleCheckoutCancel($request, 'stripe');

        $this->assertTrue($response->isRedirect());
        $this->assertStringEndsWith('/user/shop/checkout', $response->getTargetUrl());
    }

    #[Test]
    public function it_validates_checkout_token_for_payment_intent_setup()
    {
        $request = new Request([
            'checkout_token' => 'invalid-token',
            'amount' => 100,
            'currency' => 'USD',
        ]);
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->setupPaymentIntent($request, 'stripe');

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Payment setup failed', $data['message']);
    }

    #[Test]
    public function it_validates_checkout_token_for_payment_confirmation()
    {
        $request = new Request([
            'checkout_token' => 'invalid-token',
        ]);
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->confirmPayment($request, 'stripe');

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Payment confirmation failed', $data['error']);
    }

    #[Test]
    public function it_handles_unsupported_payment_provider_for_setup()
    {
        // Create checkout
        $checkout = Checkout::factory()->create([
            'token' => Str::random(32),
            'grand_total' => 100,
            'currency' => 'USD',
        ]);

        $request = new Request([
            'checkout_token' => $checkout->token,
            'amount' => 100,
            'currency' => 'USD',
        ]);
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->setupPaymentIntent($request, 'unsupported_provider');

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('message', $data);
    }

    #[Test]
    public function it_handles_unsupported_payment_provider_for_confirmation()
    {
        // Create checkout
        $checkout = Checkout::factory()->create([
            'token' => Str::random(32),
            'grand_total' => 100,
            'currency' => 'USD',
        ]);

        $request = new Request([
            'checkout_token' => $checkout->token,
        ]);
        $request->setLaravelSession(app('session')->driver());

        $controller = new ShopController();
        $response = $controller->confirmPayment($request, 'unsupported_provider');

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Payment confirmation failed', $data['error']);
    }

    #[Test]
    public function it_processes_complete_stripe_checkout_flow()
    {
        // 1. Add items to cart
        $cartController = new CartController();
        $addRequest = new Request([
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'quantity' => 3 // 3 × $15.00 = $45.00
        ]);
        $addRequest->setLaravelSession(app('session')->driver());
        $cartController->add($addRequest);

        // 2. Create checkout from cart
        $checkoutController = new CheckoutController();
        $checkoutRequest = new Request();
        $checkoutRequest->setLaravelSession(app('session')->driver());
        $checkoutResponse = $checkoutController->index($checkoutRequest);
        $checkoutData = $checkoutResponse->getData(true);

        // 3. Update customer information
        $updateRequest = new Request([
            'email' => 'stripe-customer@example.com',
            'first_name' => 'Stripe',
            'last_name' => 'Customer',
            'phone_number' => '+1234567890'
        ]);
        $updateRequest->setLaravelSession(app('session')->driver());
        $updateResponse = $checkoutController->update($updateRequest, $checkoutData['token']);
        $updateData = $updateResponse->getData(true);

        // 4. Setup Stripe payment intent
        $shopController = new ShopController();
        $paymentRequest = new Request([
            'checkout_token' => $updateData['data']['token'],
            'amount' => $updateData['data']['grand_total'],
            'currency' => 'USD',
        ]);
        $paymentRequest->setLaravelSession(app('session')->driver());
        $setupResponse = $shopController->setupPaymentIntent($paymentRequest, 'stripe');

        // 5. Test that confirm payment fails with invalid payment intent ID (expected behavior)
        $confirmRequest = new Request([
            'checkout_token' => $updateData['data']['token'],
            'payment_intent_id' => 'pi_test_' . Str::random(24), // Mock payment intent ID
        ]);
        $confirmRequest->setLaravelSession(app('session')->driver());
        $confirmResponse = $shopController->confirmPayment($confirmRequest, 'stripe');

        // Assertions
        $this->assertEquals(200, $setupResponse->getStatusCode());
        $this->assertEquals(500, $confirmResponse->getStatusCode()); // Should fail with invalid payment intent

        $setupData = $setupResponse->getData(true);
        $confirmData = $confirmResponse->getData(true);

        // Check setup response structure
        $this->assertArrayHasKey('client_secret', $setupData);
        $this->assertArrayHasKey('payment_intent_id', $setupData);

        // Check confirm response structure (error response)
        $this->assertArrayHasKey('error', $confirmData);
        $this->assertEquals('Payment confirmation failed', $confirmData['error']);

        // Verify customer data was saved
        $this->assertEquals('stripe-customer@example.com', $updateData['data']['email']);
        $this->assertEquals('Stripe', $updateData['data']['first_name']);
        $this->assertEquals('Customer', $updateData['data']['last_name']);

        // Verify totals
        $this->assertEquals(45.00, $updateData['data']['sub_total']);
        $this->assertArrayHasKey('grand_total', $updateData['data']);
    }
}
