<?php

namespace Workbench\App\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\Order;
use Coderstm\Repository\InvoiceRepository;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('line_items')->latest()->take(6)->get();

        return view('order-form', compact('orders'));
    }

    public function createOrder(Request $request)
    {
        // Parse customer name
        $nameParts = $request->filled('customer_name')
            ? explode(' ', trim($request->customer_name), 2)
            : [fake()->firstName(), fake()->lastName()];

        $firstName = $nameParts[0] ?? fake()->firstName();
        $lastName = $nameParts[1] ?? fake()->lastName();
        $email = $request->customer_email ?: fake()->unique()->safeEmail();

        $user = Coderstm::$userModel::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);

        // Prepare line items for calculation
        $itemsCount = $request->input('items_count', 3);
        $basePrice = floatval($request->input('total', 99.99)) / $itemsCount;

        $lineItems = [];
        for ($i = 1; $i <= $itemsCount; $i++) {
            $quantity = rand(1, 3);
            $price = round($basePrice, 2);
            $lineItems[] = [
                'title' => fake()->randomElement([
                    'Premium Subscription',
                    'Standard Plan',
                    'Product License',
                    'Service Package',
                    'Digital Download',
                    'Consulting Hours',
                ]).' #'.$i,
                'quantity' => $quantity,
                'price' => $price,
                'taxable' => true,
                'is_custom' => true,
            ];
        }

        // Use InvoiceRepository to calculate totals
        $invoice = new InvoiceRepository([
            'customer_id' => $user->id,
            'line_items' => $lineItems,
            'collect_tax' => true,
            'billing_address' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => fake()->phoneNumber(),
                'address_1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postcode' => fake()->postcode(),
                'country' => fake()->country(),
            ],
        ]);

        // Create order with calculated totals
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'status' => $request->input('status', 'pending'),
            'payment_status' => $request->input('payment_status', 'pending'),
            'sub_total' => $invoice->sub_total,
            'tax_total' => $invoice->tax_total,
            'shipping_total' => 0,
            'discount_total' => $invoice->discount_total,
            'grand_total' => $invoice->grand_total,
            'billing_address' => $invoice->billing_address,
        ]);

        // Create line items from the invoice data
        foreach ($invoice->line_items as $item) {
            $order->line_items()->create([
                'title' => $item->title ?? $item['title'],
                'quantity' => $item->quantity ?? $item['quantity'],
                'price' => $item->price ?? $item['price'],
                'taxable' => $item->taxable ?? $item['taxable'] ?? true,
                'is_custom' => $item->is_custom ?? $item['is_custom'] ?? true,
            ]);
        }

        // Reload order with relationships
        $order->load('line_items');

        // Redirect to payment page if requested
        if ($request->boolean('redirect_to_payment')) {
            return redirect()->route('payment', ['token' => $order->key])
                ->with('success', 'Order created successfully! Order #'.$order->order_number);
        }

        // Otherwise, redirect back to the order test page
        return redirect()->route('order-form')
            ->with('success', 'Order #'.$order->order_number.' created successfully!');
    }

    public function showPaymentPage($token = null)
    {
        // Get available payment methods
        $paymentMethods = PaymentMethod::toPublic();

        // Pass only the token to the view, order will be loaded via API
        return view('payment', compact('paymentMethods', 'token'));
    }
}
