<?php

namespace Tests\Unit\Shop;

use Coderstm\Models\Coupon;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Checkout;
use PHPUnit\Framework\Attributes\Test;
use Coderstm\Models\Shop\Cart\LineItem;
use Coderstm\Repository\CheckoutRepository;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Tests\TestCase;
use Orchestra\Testbench\Concerns\WithWorkbench;

class CheckoutRepositoryTest extends TestCase
{
    use WithWorkbench;

    protected $checkout;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a real checkout for testing
        $this->checkout = Checkout::create([
            'token' => 'test-token-' . uniqid(),
            'email' => 'test@example.com',
            'currency' => 'USD',
            'collect_tax' => true,
            'status' => 'draft'
        ]);
    }

    #[Test]
    public function it_creates_repository_from_checkout()
    {
        $repository = CheckoutRepository::fromCheckout($this->checkout);

        $this->assertInstanceOf(CheckoutRepository::class, $repository);
        $this->assertNotNull($repository->checkout);
        $this->assertEquals($this->checkout->token, $repository->checkout->token);
        $this->assertEquals($this->checkout->email, $repository->checkout->email);
    }

    #[Test]
    public function it_initializes_with_line_items_and_discount()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2, 'taxable' => true],
            ['id' => 2, 'price' => 15.00, 'quantity' => 1, 'taxable' => false],
        ];

        $discount = [
            'type' => 'percentage',
            'value' => 10,
            'description' => 'Test Discount'
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'discount' => $discount,
            'collect_tax' => false
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repository->line_items);
        $this->assertCount(2, $repository->line_items);
        $this->assertInstanceOf(LineItem::class, $repository->line_items->first());

        $this->assertInstanceOf(DiscountLine::class, $repository->discount);
        $this->assertEquals(10, $repository->discount->value);
        $this->assertEquals('percentage', $repository->discount->type);
    }

    #[Test]
    public function it_calculates_totals_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // $20.00
            ['id' => 2, 'price' => 15.00, 'quantity' => 3], // $45.00
        ];

        $discount = [
            'type' => 'fixed_amount',
            'value' => 5.00,
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'discount' => $discount,
            'collect_tax' => false // No tax for simple calculation
        ]);

        $this->assertEquals(65.00, $repository->sub_total);
        $this->assertEquals(5.00, $repository->discount_total);
        $this->assertEquals(0.00, $repository->tax_total);
        $this->assertEquals(60.00, $repository->grand_total); // $65 - $5
    }

    #[Test]
    public function it_calculates_percentage_discount_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 100.00, 'quantity' => 1], // $100.00
        ];

        $discount = [
            'type' => 'percentage',
            'value' => 15, // 15%
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'discount' => $discount,
            'collect_tax' => false
        ]);

        $this->assertEquals(100.00, $repository->sub_total);
        $this->assertEquals(15.00, $repository->discount_total); // 15% of $100
        $this->assertEquals(85.00, $repository->grand_total); // $100 - $15
    }

    #[Test]
    public function it_handles_empty_discount()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2],
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'discount' => [],
            'collect_tax' => false
        ]);

        $this->assertNull($repository->discount);
        $this->assertEquals(0.00, $repository->discount_total);
        $this->assertEquals(20.00, $repository->grand_total);
    }

    #[Test]
    public function it_handles_null_discount()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2],
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'collect_tax' => false
        ]);

        $this->assertNull($repository->discount);
        $this->assertEquals(0.00, $repository->discount_total);
        $this->assertEquals(20.00, $repository->grand_total);
    }

    #[Test]
    public function it_validates_line_items_as_array()
    {
        // $this->markTestSkipped('This test is skipped because it is not applicable in the current context.');
        $this->expectException(\InvalidArgumentException::class);

        // This should fail because line_items must be an array
        new CheckoutRepository([
            'line_items' => 'invalid_string'
        ]);
    }

    #[Test]
    public function it_calculates_taxable_totals()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2, 'taxable' => true],  // $20.00 taxable
            ['id' => 2, 'price' => 15.00, 'quantity' => 1, 'taxable' => false], // $15.00 not taxable
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'tax_lines' => [
                ['id' => 1, 'rate' => 10, 'type' => 'normal', 'name' => 'Sales Tax'] // 10% tax
            ],
            'collect_tax' => true
        ]);

        $this->assertEquals(35.00, $repository->sub_total);
        $this->assertEquals(20.00, $repository->taxable_sub_total);
        $this->assertEquals(2.00, $repository->tax_total); // 10% of $20.00
        $this->assertEquals(2, $repository->taxable_line_items); // 2 line item is taxable
        $this->assertEquals(3, $repository->total_line_items); // 2 + 1 = 3 total quantity
    }

    #[Test]
    public function it_calculates_taxable_totals_using_complex_taxes()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2, 'taxable' => true],  // $20.00 taxable
            ['id' => 2, 'price' => 15.00, 'quantity' => 1, 'taxable' => false], // $15.00 not taxable
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'tax_lines' => [
                ['id' => 1, 'rate' => 10, 'type' => 'compounded', 'name' => 'City Tax'], // 10% tax
                ['id' => 2, 'rate' => 10, 'type' => 'normal', 'name' => 'State Tax'], // 10% tax
            ],
            'collect_tax' => true
        ]);

        $this->assertEquals(35.00, $repository->sub_total);
        $this->assertEquals(20.00, $repository->taxable_sub_total);
        $this->assertEquals(4.20, $repository->tax_total); // 2.00 (normal) + 2.20 (compounded)
        $this->assertEquals(2, $repository->taxable_line_items); // 2 line item is taxable
        $this->assertEquals(3, $repository->total_line_items); // 2 + 1 = 3 total quantity
    }

    #[Test]
    public function it_applies_discount_with_checkout_model()
    {
        // Add line items to the checkout
        $lineItem1 = new LineItem(['price' => 25.00, 'quantity' => 2, 'taxable' => true]);
        $lineItem2 = new LineItem(['price' => 50.00, 'quantity' => 1, 'taxable' => false]);

        $this->checkout->setRelation('line_items', collect([$lineItem1, $lineItem2]));

        $repository = CheckoutRepository::fromCheckout($this->checkout);

        $this->assertEquals(100.00, $repository->sub_total); // (25*2) + (50*1)
        $this->assertEquals(50.00, $repository->taxable_sub_total); // Only first item is taxable
    }

    #[Test]
    public function it_handles_customer_data()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-1234'
        ];

        $repository = new CheckoutRepository([
            'customer' => $customerData
        ]);

        $this->assertEquals($customerData, $repository->customer);
        $this->assertEquals('John Doe', $repository->customer['name']);
    }

    #[Test]
    public function it_handles_billing_address()
    {
        $billingAddress = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345',
            'country' => 'US'
        ];

        $repository = new CheckoutRepository([
            'billing_address' => $billingAddress
        ]);

        $this->assertEquals($billingAddress, $repository->billing_address);
        $this->assertEquals('Anytown', $repository->billing_address['city']);
    }

    #[Test]
    public function it_refreshes_taxes_based_on_billing_address()
    {
        $billingAddress = [
            'country' => 'United States',
            'state' => 'CA',
            'city' => 'Los Angeles'
        ];

        // Mock global tax functions if they don't exist
        if (!function_exists('billing_address_tax')) {
            function billing_address_tax($address)
            {
                return [
                    ['id' => 1, 'rate' => 8.25, 'name' => 'CA Sales Tax']
                ];
            }
        }

        if (!function_exists('default_tax')) {
            function default_tax()
            {
                return [
                    ['id' => 1, 'rate' => 0, 'name' => 'No Tax']
                ];
            }
        }

        $this->checkout->billing_address = $billingAddress;
        $this->checkout->save();

        $repository = CheckoutRepository::fromCheckout($this->checkout);
        $repository->recalculate();

        // Check that tax_lines is accessible and is a Collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repository->tax_lines);
    }

    #[Test]
    public function it_calculates_discount_per_item()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // 2 items
            ['id' => 2, 'price' => 5.00, 'quantity' => 2],  // 2 items
        ];

        $discount = [
            'type' => 'fixed_amount',
            'value' => 8.00, // $8 total discount
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'discount' => $discount
        ]);

        $this->assertEquals(4, $repository->total_line_items);
        $this->assertEquals(8.00, $repository->discount_total);
        $this->assertEquals(2.00, $repository->discount_per_item); // $8 / 4 items
    }

    #[Test]
    public function it_handles_empty_line_items()
    {
        $repository = new CheckoutRepository([
            'line_items' => [],
            'collect_tax' => false
        ]);

        $this->assertEquals(0, $repository->total_line_items);
        $this->assertEquals(0, $repository->taxable_line_items);
        $this->assertEquals(0.00, $repository->sub_total);
        $this->assertEquals(0.00, $repository->taxable_sub_total);
        $this->assertEquals(0.00, $repository->grand_total);
    }

    #[Test]
    public function it_converts_checkout_line_items_to_array()
    {
        // Test that fromCheckout properly converts Collection to array
        $lineItem = new LineItem(['price' => 15.00, 'quantity' => 1, 'taxable' => true]);
        $this->checkout->setRelation('line_items', collect([$lineItem]));

        $repository = CheckoutRepository::fromCheckout($this->checkout);

        // line_items should be accessible as Collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repository->line_items);
        $this->assertCount(1, $repository->line_items);
        $this->assertEquals(15.00, $repository->line_items->first()->price);
    }

    #[Test]
    public function it_gets_checkout_data()
    {
        $repository = CheckoutRepository::fromCheckout($this->checkout);

        $data = $repository->getCheckoutData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('sub_total', $data);
        $this->assertArrayHasKey('tax_total', $data);
        $this->assertArrayHasKey('discount_total', $data);
        $this->assertArrayHasKey('grand_total', $data);

        $this->assertEquals($this->checkout->token, $data['token']);
        $this->assertEquals($this->checkout->email, $data['email']);
    }

    #[Test]
    public function it_gets_checkout_data_read_only()
    {
        $this->checkout->sub_total = 20.00;
        $this->checkout->tax_total = 2.00;
        $this->checkout->discount_total = 5.00;
        $this->checkout->grand_total = 17.00;
        $this->checkout->save();

        $repository = CheckoutRepository::fromCheckout($this->checkout);

        $data = $repository->getCheckoutData(false);

        $this->assertIsArray($data);
        $this->assertEquals(20.00, $data['sub_total']);
        $this->assertEquals(2.00, $data['tax_total']);
        $this->assertEquals(5.00, $data['discount_total']);
        $this->assertEquals(17.00, $data['grand_total']);
    }

    #[Test]
    public function it_handles_multiple_attribute_changes()
    {
        $repository = new CheckoutRepository(['collect_tax' => false]);

        // Set line items
        $lineItems = [
            ['id' => 1, 'price' => 100.00, 'quantity' => 2] // $200 total
        ];
        $repository->line_items = $lineItems;

        // Set discount
        $discount = [
            'type' => 'percentage',
            'value' => 10 // 10% discount
        ];
        $repository->discount = $discount;

        // Set customer
        $customer = [
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];
        $repository->customer = $customer;

        // Verify all accessors work correctly
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repository->line_items);
        $this->assertEquals(200.00, $repository->sub_total);

        $this->assertInstanceOf(DiscountLine::class, $repository->discount);
        $this->assertEquals(20.00, $repository->discount_total); // 10% of $200

        $this->assertEquals($customer, $repository->customer);
        $this->assertEquals('Test Customer', $repository->customer['name']);

        // Verify calculated totals work with normalized data
        $this->assertEquals(180.00, $repository->grand_total); // $200 - $20 discount (no tax)
    }

    #[Test]
    public function it_validates_repository_rules()
    {
        $repository = new CheckoutRepository();

        $rules = $repository->rules();

        $this->assertArrayHasKey('customer', $rules);
        $this->assertArrayHasKey('line_items', $rules);
        $this->assertArrayHasKey('discount', $rules);
        $this->assertArrayHasKey('tax_lines', $rules);
        $this->assertArrayHasKey('collect_tax', $rules);
    }

    #[Test]
    public function it_converts_to_array_properly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2],
        ];

        $discount = [
            'type' => 'fixed_amount',
            'value' => 5.00,
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'discount' => $discount,
            'collect_tax' => false
        ]);

        $array = $repository->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('sub_total', $array);
        $this->assertArrayHasKey('discount_total', $array);
        $this->assertArrayHasKey('grand_total', $array);
        $this->assertEquals(20.00, $array['sub_total']);
        $this->assertEquals(5.00, $array['discount_total']);

        // Tax should be calculated on $20 (subtotal) - $5 (discount) = $15
        // If tax lines exist, tax_total should be > 0
        if ($repository->tax_lines->isNotEmpty()) {
            $this->assertGreaterThan(0, $array['tax_total']);
            // Grand total = $20 (subtotal) + tax - $5 (discount)
            $expectedGrandTotal = 20 + $array['tax_total'] - 5;
            $this->assertEquals($expectedGrandTotal, $array['grand_total']);
        } else {
            // If no tax lines, should equal no-tax calculation
            $this->assertEquals(15.00, $array['grand_total']);
        }
    }

    #[Test]
    public function it_auto_applies_coupon_and_shows_in_applied_coupons()
    {
        // Create a product for the coupon to apply to
        $product = Product::factory()->create();

        $coupon = Coupon::factory()->create([
            'promotion_code' => 'AUTO10',
            'discount_type' => 'percentage',
            'value' => 10,
            'type' => 'product',
            'active' => true,
        ]);

        // Add the product to the coupon's applicable products
        $coupon->products()->attach($product->id);

        $checkout = Checkout::create([
            'token' => 'test-token-' . uniqid(),
            'email' => 'test@example.com',
            'currency' => 'USD',
            'collect_tax' => true,
            'status' => 'draft'
        ]);

        $checkout->setRelation('line_items', collect([
            new LineItem(['product_id' => $product->id, 'price' => 100.00, 'quantity' => 1, 'taxable' => true])
        ]));

        $repository = CheckoutRepository::fromCheckout($checkout);

        $repository->recalculate();

        $appliedCoupons = $repository->getAppliedCoupons();

        $this->assertNotEmpty($appliedCoupons, 'Auto-applied coupon should be present');
        $this->assertEquals('AUTO10', $appliedCoupons[0]['code']);
    }

    #[Test]
    public function it_manually_applies_coupon_and_shows_in_applied_coupons()
    {
        // Create a product for the coupon to apply to
        $product = Product::factory()->create();

        // Seed a coupon for manual application
        $coupon = Coupon::factory()->create([
            'promotion_code' => 'MANUAL20',
            'discount_type' => 'percentage',
            'value' => 20,
            'type' => 'product',
            'active' => true,
        ]);

        // Add the product to the coupon's applicable products
        $coupon->products()->attach($product->id);

        $lineItems = [
            ['product_id' => $product->id, 'price' => 100.00, 'quantity' => 1, 'taxable' => true],
        ];

        $repository = new CheckoutRepository([
            'line_items' => $lineItems,
            'collect_tax' => false
        ]);

        // Manually apply coupon
        $repository->applyCoupon('MANUAL20');

        $appliedCoupons = $repository->getAppliedCoupons();
        $this->assertNotEmpty($appliedCoupons, 'Manually applied coupon should be present');
        $this->assertEquals('MANUAL20', $appliedCoupons[0]['code']);
    }
}
