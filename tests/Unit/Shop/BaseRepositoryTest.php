<?php

namespace Tests\Unit\Shop;

use Coderstm\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Coderstm\Models\Shop\Cart\LineItem;
use Coderstm\Repository\BaseRepository;
use Coderstm\Models\Shop\Order\DiscountLine;
use Orchestra\Testbench\Concerns\WithWorkbench;

class BaseRepositoryTest extends TestCase
{
    use WithWorkbench;

    protected function createTestRepository($attributes = [])
    {
        return new class($attributes) extends BaseRepository {
            // Anonymous class to test the abstract BaseRepository
        };
    }

    #[Test]
    public function it_initializes_with_default_values()
    {
        $repo = $this->createTestRepository();

        $this->assertTrue($repo->collect_tax);
        $this->assertFalse($repo->exists);
        $this->assertFalse($repo->timestamps);
    }

    #[Test]
    public function it_validates_attributes_on_construction()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Invalid discount (string instead of array/object)
        $this->createTestRepository(['discount' => 'invalid']);
    }

    #[Test]
    public function it_sets_default_tax_lines_when_empty()
    {
        $repo = $this->createTestRepository();

        // Should have default tax lines set
        $this->assertNotEmpty($repo->tax_lines);
    }

    #[Test]
    public function it_uses_billing_address_for_tax_calculation()
    {
        $billingAddress = [
            'country' => 'United States',
            'state' => 'California',
            'state_code' => 'CA',
            'city' => 'San Francisco'
        ];

        $repo = $this->createTestRepository(['billing_address' => $billingAddress]);

        // Tax lines should be a collection (even if empty)
        $this->assertTrue($repo->tax_lines->isNotEmpty(), 'No tax lines found');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repo->tax_lines);
    }

    #[Test]
    public function it_normalizes_discount_data_correctly()
    {
        // Test with array data
        $discountData = [
            'type' => 'percentage',
            'value' => 15,
            'description' => 'Test Discount'
        ];

        $repo = $this->createTestRepository(['discount' => $discountData]);

        $discount = $repo->discount;
        $this->assertInstanceOf(DiscountLine::class, $discount);
        $this->assertEquals(15, $discount->value);
        $this->assertTrue($repo->hasDiscount());
    }

    #[Test]
    public function it_handles_empty_discount()
    {
        $repo = $this->createTestRepository(['discount' => []]);

        $this->assertNull($repo->discount);
        $this->assertFalse($repo->hasDiscount());
    }

    #[Test]
    public function it_handles_discount_line_object_directly()
    {
        $discountLine = new DiscountLine([
            'type' => 'fixed_amount',
            'value' => 25.00,
            'description' => 'Fixed Discount'
        ]);

        $repo = $this->createTestRepository(['discount' => $discountLine]);

        $discount = $repo->discount;
        $this->assertInstanceOf(DiscountLine::class, $discount);
        $this->assertEquals(25.00, $discount->value);
        $this->assertTrue($repo->hasDiscount());
    }

    #[Test]
    public function it_normalizes_line_items_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2, 'taxable' => true],
            ['id' => 2, 'price' => 15.00, 'quantity' => 1, 'taxable' => false],
            ['id' => 3, 'price' => 10.00, 'quantity' => 4, 'taxable' => true],
        ];

        $repo = $this->createTestRepository(['line_items' => $lineItems]);

        $items = $repo->line_items;
        $this->assertCount(3, $items);
        $this->assertInstanceOf(LineItem::class, $items->first());
        $this->assertEquals(7, $repo->total_line_items); // 2 + 1 + 4 = 7 total quantity
        $this->assertEquals(6, $repo->taxable_line_items); // 2 + 4 = 6 total quantity
    }

    #[Test]
    public function it_normalizes_customer_data()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $repo = $this->createTestRepository(['customer' => $customerData]);

        $this->assertEquals($customerData, $repo->customer);
        $this->assertEquals('John Doe', $repo->customer['name']);
    }

    #[Test]
    public function it_handles_null_customer_data()
    {
        $repo = $this->createTestRepository(['customer' => null]);

        $this->assertEquals(null, $repo->customer);
    }

    #[Test]
    public function it_normalizes_address_data()
    {
        $billingAddress = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345'
        ];

        $shippingAddress = [
            'street' => '456 Oak Ave',
            'city' => 'Another City',
            'state' => 'NY',
            'zip' => '67890'
        ];

        $repo = $this->createTestRepository([
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress
        ]);

        $this->assertEquals($billingAddress, $repo->billing_address);
        $this->assertEquals($shippingAddress, $repo->shipping_address);
    }

    #[Test]
    public function it_calculates_subtotal_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // $20.00
            ['id' => 2, 'price' => 15.00, 'quantity' => 3], // $45.00
        ];

        $repo = $this->createTestRepository(['line_items' => $lineItems]);

        $this->assertEquals(65.00, $repo->sub_total);
    }

    #[Test]
    public function it_calculates_taxable_subtotal_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2, 'taxable' => true],  // $20.00
            ['id' => 2, 'price' => 15.00, 'quantity' => 3, 'taxable' => false], // $45.00 (not taxable)
        ];

        $repo = $this->createTestRepository(['line_items' => $lineItems]);

        $this->assertEquals(65.00, $repo->sub_total);
        $this->assertEquals(20.00, $repo->taxable_sub_total);
    }

    #[Test]
    public function it_calculates_percentage_discount_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // $20.00
        ];

        $discount = [
            'type' => 'percentage',
            'value' => 10, // 10%
        ];

        $repo = $this->createTestRepository([
            'line_items' => $lineItems,
            'discount' => $discount
        ]);

        $this->assertEquals(20.00, $repo->sub_total);
        $this->assertEquals(2.00, $repo->discount_total); // 10% of $20
    }

    #[Test]
    public function it_calculates_fixed_amount_discount_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // $20.00
        ];

        $discount = [
            'type' => 'fixed_amount',
            'value' => 5.00,
        ];

        $repo = $this->createTestRepository([
            'line_items' => $lineItems,
            'discount' => $discount
        ]);

        $this->assertEquals(20.00, $repo->sub_total);
        $this->assertEquals(5.00, $repo->discount_total);
    }

    #[Test]
    public function it_calculates_grand_total_correctly()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // $20.00
        ];

        $discount = [
            'type' => 'fixed_amount',
            'value' => 3.00,
        ];

        $repo = $this->createTestRepository([
            'line_items' => $lineItems,
            'discount' => $discount,
            'collect_tax' => false // No tax for simple calculation
        ]);

        $this->assertEquals(20.00, $repo->sub_total);
        $this->assertEquals(3.00, $repo->discount_total);
        $this->assertEquals(0.00, $repo->tax_total);
        $this->assertEquals(0.00, $repo->shipping_total);
        $this->assertEquals(17.00, $repo->grand_total); // $20 - $3
    }

    #[Test]
    public function it_handles_empty_line_items()
    {
        $repo = $this->createTestRepository(['line_items' => []]);

        $this->assertEquals(0, $repo->total_line_items);
        $this->assertEquals(0, $repo->taxable_line_items);
        $this->assertEquals(0.00, $repo->sub_total);
        $this->assertEquals(0.00, $repo->taxable_sub_total);
    }

    #[Test]
    public function it_handles_null_line_items()
    {
        $repo = $this->createTestRepository();

        $this->assertEquals(0, $repo->total_line_items);
        $this->assertEquals(0.00, $repo->sub_total);
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

        $repo = $this->createTestRepository([
            'line_items' => $lineItems,
            'discount' => $discount
        ]);

        $this->assertEquals(4, $repo->total_line_items);
        $this->assertEquals(8.00, $repo->discount_total);
        $this->assertEquals(2.00, $repo->discount_per_item); // $8 / 4 items
    }

    #[Test]
    public function it_validates_repository_rules()
    {
        $repo = $this->createTestRepository();

        $rules = $repo->rules();

        $this->assertArrayHasKey('customer', $rules);
        $this->assertArrayHasKey('line_items', $rules);
        $this->assertArrayHasKey('discount', $rules);
        $this->assertArrayHasKey('tax_lines', $rules);
        $this->assertArrayHasKey('collect_tax', $rules);
    }

    #[Test]
    public function it_uses_default_tax_when_no_billing_address()
    {
        $repo = $this->createTestRepository();
        $repo->useDefaultTax();

        $this->assertNotEmpty($repo->tax_lines);
    }

    #[Test]
    public function it_uses_billing_address_tax_when_available()
    {
        $billingAddress = [
            'country' => 'US',
            'state' => 'CA'
        ];

        $repo = $this->createTestRepository(['billing_address' => $billingAddress]);
        $repo->useDefaultTax();

        $this->assertNotEmpty($repo->tax_lines);
    }

    #[Test]
    public function it_sets_default_taxable_for_line_items()
    {
        $lineItems = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2], // no taxable specified
        ];

        $repo = $this->createTestRepository(['line_items' => $lineItems]);

        $items = $repo->line_items;
        $firstItem = $items->first();
        $this->assertTrue($firstItem->taxable); // Should default to true
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

        $repo = $this->createTestRepository([
            'line_items' => $lineItems,
            'discount' => $discount
        ]);

        $array = $repo->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('sub_total', $array);
        $this->assertArrayHasKey('discount_total', $array);
        $this->assertArrayHasKey('grand_total', $array);
        $this->assertEquals(20.00, $array['sub_total']);
        $this->assertEquals(5.00, $array['discount_total']);
    }

    #[Test]
    public function it_handles_attribute_setting_via_constructor()
    {
        $discountData = [
            'type' => 'percentage',
            'value' => 10
        ];

        $lineItems = [
            ['id' => 1, 'price' => 100.00, 'quantity' => 1]
        ];

        $repo = $this->createTestRepository([
            'discount' => $discountData,
            'line_items' => $lineItems
        ]);

        // Attributes should be normalized through constructor
        $this->assertInstanceOf(DiscountLine::class, $repo->discount);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repo->line_items);
        $this->assertInstanceOf(LineItem::class, $repo->line_items->first());
    }

    #[Test]
    public function it_tests_discount_accessor_get()
    {
        // Test getting with array data
        $discountData = [
            'type' => 'percentage',
            'value' => 15
        ];

        $repo = $this->createTestRepository(['discount' => $discountData]);

        // Accessor should return DiscountLine object
        $discount = $repo->discount;
        $this->assertInstanceOf(DiscountLine::class, $discount);
        $this->assertEquals(15, $discount->value);
        $this->assertEquals('percentage', $discount->type);
    }

    #[Test]
    public function it_tests_discount_accessor_set()
    {
        $repo = $this->createTestRepository();

        // Test setting with array
        $discountData = [
            'type' => 'fixed_amount',
            'value' => 25.00
        ];

        $repo->discount = $discountData;

        // Should be normalized to DiscountLine object
        /** @var DiscountLine $discount */
        $discount = $repo->discount;
        $this->assertInstanceOf(DiscountLine::class, $discount);
        $this->assertEquals(25.00, $discount->value);
        $this->assertEquals('fixed_amount', $discount->type);
    }

    #[Test]
    public function it_tests_discount_accessor_set_with_object()
    {
        $repo = $this->createTestRepository();

        // Test setting with DiscountLine object
        $discountLine = new DiscountLine([
            'type' => 'percentage',
            'value' => 20
        ]);

        $repo->discount = $discountLine;

        // Should return the same object
        $this->assertInstanceOf(DiscountLine::class, $repo->discount);
        $this->assertEquals(20, $repo->discount->value);
        $this->assertEquals('percentage', $repo->discount->type);
    }

    #[Test]
    public function it_tests_discount_accessor_set_with_null()
    {
        $repo = $this->createTestRepository();

        // Test setting with null
        $repo->discount = null;

        // Should return null
        $this->assertNull($repo->discount);
        $this->assertFalse($repo->hasDiscount());
    }

    #[Test]
    public function it_tests_line_items_accessor_get()
    {
        $lineItemsData = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2],
            ['id' => 2, 'price' => 15.00, 'quantity' => 1, 'taxable' => false]
        ];

        $repo = $this->createTestRepository(['line_items' => $lineItemsData]);

        // Accessor should return Collection of LineItem objects
        $lineItems = $repo->line_items;
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $lineItems);
        $this->assertCount(2, $lineItems);
        $this->assertInstanceOf(LineItem::class, $lineItems->first());

        // Test default taxable behavior
        $this->assertTrue($lineItems->first()->taxable); // Should default to true
        $this->assertFalse($lineItems->last()->taxable); // Should preserve false
    }

    #[Test]
    public function it_tests_line_items_accessor_set()
    {
        $repo = $this->createTestRepository();

        // Test setting line items
        $lineItemsData = [
            ['id' => 1, 'price' => 25.00, 'quantity' => 3],
            ['id' => 2, 'price' => 50.00, 'quantity' => 1]
        ];

        $repo->line_items = $lineItemsData;

        // Should be normalized to Collection of LineItem objects
        /** @var LineItem $lineItems */
        $lineItems = $repo->line_items;
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $lineItems);
        $this->assertCount(2, $lineItems->toArray());

        /** @var LineItem $firstItem */
        $firstItem = $lineItems->first();
        $this->assertInstanceOf(LineItem::class, $firstItem);

        // Verify values - IDE should now understand these properties
        $this->assertEquals(25.00, $firstItem->price);
        $this->assertEquals(3, $firstItem->quantity);
        $this->assertTrue($firstItem->taxable); // Should default to true
    }

    #[Test]
    public function it_tests_customer_accessor_get_and_set()
    {
        $repo = $this->createTestRepository();

        // Test setting customer data
        $customerData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '555-1234'
        ];

        $repo->customer = $customerData;

        // Should return the array as-is
        $this->assertEquals($customerData, $repo->customer);
        $this->assertEquals('Jane Smith', $repo->customer['name']);

        // Test setting null
        $repo->customer = null;
        $this->assertEquals(null, $repo->customer);
    }

    #[Test]
    public function it_tests_billing_address_accessor_get_and_set()
    {
        $repo = $this->createTestRepository();

        // Test setting billing address
        $addressData = [
            'street' => '789 Pine St',
            'city' => 'Seattle',
            'state' => 'WA',
            'zip' => '98101',
            'country' => 'US'
        ];

        $repo->billing_address = $addressData;

        // Should return the array as-is
        $this->assertEquals($addressData, $repo->billing_address);
        $this->assertEquals('Seattle', $repo->billing_address['city']);

        // Test setting null
        $repo->billing_address = null;
        $this->assertEquals(null, $repo->billing_address);
    }

    #[Test]
    public function it_tests_shipping_address_accessor_get_and_set()
    {
        $repo = $this->createTestRepository();

        // Test setting shipping address
        $addressData = [
            'street' => '321 Oak Blvd',
            'city' => 'Portland',
            'state' => 'OR',
            'zip' => '97201',
            'country' => 'US'
        ];

        $repo->shipping_address = $addressData;

        // Should return the array as-is
        $this->assertEquals($addressData, $repo->shipping_address);
        $this->assertEquals('Portland', $repo->shipping_address['city']);

        // Test setting null
        $repo->shipping_address = null;
        $this->assertEquals(null, $repo->shipping_address);
    }

    #[Test]
    public function it_tests_multiple_accessor_changes()
    {
        $repo = $this->createTestRepository(['collect_tax' => false]);

        // Test changing multiple attributes and verify they all work together

        // Set line items
        $lineItems = [
            ['id' => 1, 'price' => 100.00, 'quantity' => 2] // $200 total
        ];
        $repo->line_items = $lineItems;

        // Set discount
        $discount = [
            'type' => 'percentage',
            'value' => 10 // 10% discount
        ];
        $repo->discount = $discount;

        // Set customer
        $customer = [
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];
        $repo->customer = $customer;

        // Verify all accessors work correctly
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repo->line_items);
        $this->assertEquals(200.00, $repo->sub_total);

        $this->assertInstanceOf(DiscountLine::class, $repo->discount);
        $this->assertEquals(20.00, $repo->discount_total); // 10% of $200

        $this->assertEquals($customer, $repo->customer);
        $this->assertEquals('Test Customer', $repo->customer['name']);

        // Verify calculated totals work with normalized data
        $this->assertEquals(180.00, $repo->grand_total); // $200 - $20 discount (no tax)
    }

    #[Test]
    public function it_tests_multiple_accessor_changes_with_tax()
    {
        $repo = $this->createTestRepository(['collect_tax' => true]);

        // Test changing multiple attributes and verify they all work together with tax

        // Set line items
        $lineItems = [
            ['id' => 1, 'price' => 100.00, 'quantity' => 2] // $200 total
        ];
        $repo->line_items = $lineItems;

        // Set discount
        $discount = [
            'type' => 'percentage',
            'value' => 10 // 10% discount
        ];
        $repo->discount = $discount;

        // Set customer
        $customer = [
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];
        $repo->customer = $customer;

        // Verify all accessors work correctly
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repo->line_items);
        $this->assertEquals(200.00, $repo->sub_total);

        $this->assertInstanceOf(DiscountLine::class, $repo->discount);
        $this->assertEquals(20.00, $repo->discount_total); // 10% of $200

        $this->assertEquals($customer, $repo->customer);
        $this->assertEquals('Test Customer', $repo->customer['name']);

        // Verify tax calculation
        $this->assertTrue($repo->collect_tax);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $repo->tax_lines);

        // Tax should be calculated on: $200 (subtotal) - $20 (discount) = $180
        // If tax lines exist, tax_total should be > 0
        if ($repo->tax_lines->isNotEmpty()) {
            $this->assertGreaterThan(0, $repo->tax_total);
            // Grand total = $200 (subtotal) + tax - $20 (discount)
            $expectedGrandTotal = 200.00 + $repo->tax_total - 20.00;
            $this->assertEquals($expectedGrandTotal, $repo->grand_total);
        } else {
            // If no tax lines, should equal no-tax calculation
            $this->assertEquals(180.00, $repo->grand_total);
        }
    }

    #[Test]
    public function it_tests_set_discount_method_directly()
    {
        $repo = $this->createTestRepository();

        // Use reflection to test the setDiscount method directly
        $reflection = new \ReflectionClass($repo);
        $method = $reflection->getMethod('setDiscount');
        $method->setAccessible(true);

        // Test with array
        $discountData = ['type' => 'percentage', 'value' => 15];
        $result = $method->invoke($repo, $discountData);
        $this->assertInstanceOf(DiscountLine::class, $result);
        $this->assertEquals(15, $result->value);

        // Test with DiscountLine object
        $discountLine = new DiscountLine(['type' => 'fixed_amount', 'value' => 20]);
        $result = $method->invoke($repo, $discountLine);
        $this->assertInstanceOf(DiscountLine::class, $result);
        $this->assertEquals(20, $result->value);

        // Test with null
        $result = $method->invoke($repo, null);
        $this->assertNull($result);

        // Test with string (should return null)
        $result = $method->invoke($repo, 'invalid');
        $this->assertNull($result);
    }

    #[Test]
    public function it_tests_set_line_items_method_directly()
    {
        $repo = $this->createTestRepository();

        // Use reflection to test the setLineItems method directly
        $reflection = new \ReflectionClass($repo);
        $method = $reflection->getMethod('setLineItems');
        $method->setAccessible(true);

        // Test with array
        $lineItemsData = [
            ['id' => 1, 'price' => 10.00, 'quantity' => 2],
            ['id' => 2, 'price' => 15.00, 'quantity' => 1, 'taxable' => false]
        ];

        $result = $method->invoke($repo, $lineItemsData);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(LineItem::class, $result->first());

        // Test default taxable behavior
        $this->assertTrue($result->first()->taxable);
        $this->assertFalse($result->last()->taxable);

        // Test with empty array
        $result = $method->invoke($repo, []);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(0, $result);

        // Test with null
        $result = $method->invoke($repo, null);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_tests_accessor_consistency_between_get_and_set()
    {
        $repo = $this->createTestRepository();

        // Test that setting and then getting produces consistent results
        $originalData = [
            'type' => 'percentage',
            'value' => 25
        ];

        // Set via property
        $repo->discount = $originalData;

        // Get via property
        /** @var DiscountLine $retrievedDiscount */
        $retrievedDiscount = $repo->discount;

        // Should be normalized to DiscountLine
        $this->assertInstanceOf(DiscountLine::class, $retrievedDiscount);
        $this->assertEquals(25, $retrievedDiscount->value);
        $this->assertEquals('percentage', $retrievedDiscount->type);

        // Setting the same object again should work
        $repo->discount = $retrievedDiscount;
        /** @var DiscountLine $finalDiscount */
        $finalDiscount = $repo->discount;

        $this->assertInstanceOf(DiscountLine::class, $finalDiscount);
        $this->assertEquals(25, $finalDiscount->value);
        $this->assertEquals('percentage', $finalDiscount->type);
    }
}
