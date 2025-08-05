<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $query = Checkout::with(['user', 'cart'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by email or name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $checkouts = $query->paginate($request->per_page ?? 15);

        return response()->json($checkouts, 200);
    }

    public function show($id)
    {
        $checkout = Checkout::with(['user', 'cart.items.product', 'order'])
            ->findOrFail($id);

        return response()->json($checkout, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variant_id' => 'required|exists:variants,id',
            'products.*.quantity' => 'required|integer|min:1',
            'customer.email' => 'required|email',
            'customer.first_name' => 'required|string|max:255',
            'customer.last_name' => 'required|string|max:255',
            'customer.phone' => 'nullable|string|max:20',
            'send_link' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create a temporary cart
        $cart = Checkout::create([
            'session_id' => 'admin_' . uniqid(),
        ]);

        // Add products to cart
        foreach ($request->products as $product) {
            $cart->items()->create([
                'product_id' => $product['product_id'],
                'variant_id' => $product['variant_id'],
                'quantity' => $product['quantity'],
                'data' => $product['data'] ?? null,
            ]);
        }

        // Create checkout from cart
        $checkout = Checkout::createFromCart($cart, [
            'email' => $request->input('customer.email'),
            'first_name' => $request->input('customer.first_name'),
            'last_name' => $request->input('customer.last_name'),
            'phone' => $request->input('customer.phone'),
            'internal_note' => $request->internal_note,
        ]);

        $checkout->save();

        // Send checkout link via email if requested
        if ($request->send_link) {
            $this->sendCheckoutLink($checkout);
        }

        return response()->json([
            'data' => $checkout,
            'checkout_url' => $checkout->getCheckoutUrl(),
            'message' => 'Checkout created successfully!',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $checkout = Checkout::findOrFail($id);

        if ($checkout->status === 'completed') {
            return response()->json([
                'message' => 'Cannot update completed checkout',
            ], 400);
        }

        $checkout->update($request->only([
            'email',
            'first_name',
            'last_name',
            'phone',
            'shipping_address',
            'billing_address',
            'same_as_billing',
            'note',
            'internal_note',
            'status'
        ]));

        return response()->json([
            'data' => $checkout,
            'message' => 'Checkout updated successfully!',
        ], 200);
    }

    public function destroy($id)
    {
        $checkout = Checkout::findOrFail($id);

        if ($checkout->status === 'completed') {
            return response()->json([
                'message' => 'Cannot delete completed checkout',
            ], 400);
        }

        $checkout->delete();

        return response()->json([
            'message' => 'Checkout deleted successfully!',
        ], 200);
    }

    public function sendLink(Request $request, $id)
    {
        $checkout = Checkout::findOrFail($id);

        if (!$checkout->email) {
            return response()->json([
                'message' => 'Checkout must have an email address',
            ], 400);
        }

        $this->sendCheckoutLink($checkout);

        return response()->json([
            'message' => 'Checkout link sent successfully!',
        ], 200);
    }

    public function markAbandoned(Request $request, $id)
    {
        $checkout = Checkout::findOrFail($id);
        $checkout->markAsAbandoned();

        return response()->json([
            'data' => $checkout,
            'message' => 'Checkout marked as abandoned!',
        ], 200);
    }

    public function sendRecoveryEmail(Request $request, $id)
    {
        $checkout = Checkout::findOrFail($id);

        if (!$checkout->canSendRecoveryEmail()) {
            return response()->json([
                'message' => 'Recovery email cannot be sent for this checkout',
            ], 400);
        }

        $this->sendAbandonedCheckoutRecovery($checkout);

        return response()->json([
            'message' => 'Recovery email sent successfully!',
        ], 200);
    }

    public function statistics(Request $request)
    {
        $stats = [
            'total_checkouts' => Checkout::count(),
            'completed_checkouts' => Checkout::where('status', 'completed')->count(),
            'abandoned_checkouts' => Checkout::abandoned()->count(),
            'pending_checkouts' => Checkout::where('status', 'pending')->count(),
            'total_revenue' => Checkout::where('status', 'completed')->sum('total'),
            'average_order_value' => Checkout::where('status', 'completed')->avg('total'),
            'conversion_rate' => $this->calculateConversionRate(),
        ];

        return response()->json($stats, 200);
    }

    protected function sendCheckoutLink(Checkout $checkout)
    {
        // You would implement your email sending logic here
        // For now, this is a placeholder

        $data = [
            'checkout' => $checkout,
            'checkout_url' => $checkout->getCheckoutUrl(),
            'customer_name' => $checkout->getFullName(),
        ];

        // Mail::send('emails.checkout-link', $data, function ($message) use ($checkout) {
        //     $message->to($checkout->email, $checkout->getFullName())
        //             ->subject('Complete Your Purchase');
        // });
    }

    protected function sendAbandonedCheckoutRecovery(Checkout $checkout)
    {
        $data = [
            'checkout' => $checkout,
            'checkout_url' => $checkout->getCheckoutUrl(),
            'customer_name' => $checkout->getFullName(),
        ];

        // Mail::send('emails.abandoned-checkout', $data, function ($message) use ($checkout) {
        //     $message->to($checkout->email, $checkout->getFullName())
        //             ->subject('Complete Your Purchase - Items Still in Cart');
        // });

        $checkout->update(['recovery_email_sent_at' => now()]);
    }

    protected function calculateConversionRate()
    {
        $totalStarted = Checkout::where('status', '!=', 'draft')->count();
        $totalCompleted = Checkout::where('status', 'completed')->count();

        if ($totalStarted == 0) {
            return 0;
        }

        return round(($totalCompleted / $totalStarted) * 100, 2);
    }
}
