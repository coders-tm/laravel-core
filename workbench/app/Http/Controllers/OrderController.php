<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Resource;
use Coderstm\Events\RefundProcessed;
use App\Notifications\SendOrderInvoice;
use Coderstm\Models\Shop\Order\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request, Order $order)
    {
        $orders = $order->query();

        if ($request->filled('filter')) {
            $orders->whereRaw('(SELECT CONCAT(`first_name`, `first_name`) AS name FROM users WHERE users.id = orders.customer_id) like ?', ["%{$request->filter}%"]);
        }

        if ($request->filled('customer')) {
            $orders->whereHas('customer', function ($query) use ($request) {
                $query->where('id', $request->customer);
            });
        }

        if ($request->filled('status')) {
            $orders->whereStatus($request->status);
        }

        if ($request->boolean('deleted')) {
            $orders->onlyTrashed();
        }

        $orders = $orders->sortBy($request->sortBy, $request->descending)
            ->paginate($request->rowsPerPage ?? 15);
        return new ResourceCollection($orders);
    }

    public function store(Request $request, Order $order)
    {
        $rules = [
            'line_items' => 'required',
            'line_items.*.title' => 'required|string',
            'line_items.*.discount.value' => 'required_if:line_items.*.discount,not_null',
            'line_items.*.discount.type' => 'required_if:line_items.*.discount,not_null',
            'payment_method' => 'sometimes|exists:payment_methods,id',
        ];

        $this->validate($request, $rules);

        $order = $order->modifyOrCreate(new Resource($request->input()));

        if ($request->filled('payment_method')) {
            $order->markAsPaid($request->payment_method, [
                'note' => 'Marked the manual payment as received'
            ]);
        }

        return response()->json([
            'data' => $order->fresh([
                'line_items',
                'tax_lines',
                'discount',
                'discount',
                'location',
                'logs.admin',
            ]),
            'message' => 'Order has been created successfully!',
        ], 200);
    }

    public function show(Order $order)
    {
        return response()->json($order->loadMissing([
            'location',
            'payments',
            'logs.admin',
            'line_items',
            'tax_lines',
            'discount',
        ]), 200);
    }

    public function update(Request $request, Order $order)
    {
        // Set rules
        $rules = [
            'line_items' => 'array|required',
            'line_items.*.title' => 'required|string',
            'line_items.*.discount.value' => 'required_if:line_items.*.discount,not_null',
            'line_items.*.discount.type' => 'required_if:line_items.*.discount,not_null',
            'payment_method' => 'sometimes|exists:payment_methods,id',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        if (!$order->can_edit) {
            return response()->json([
                'message' => 'Canceled/Completed orders can’t be edited.',
            ], 422);
        }

        $order = $order->modifyOrCreate(new Resource($request->input()));

        if ($request->filled('payment_method')) {
            $order->markAsPaid($request->payment_method, [
                'note' => 'Marked the manual payment as received'
            ]);
        } else if ($order->is_paid && $order->has_due) {
            $order->markAsPartiallyPaid();
        }

        return response()->json([
            'data' => $order->fresh([
                'discount',
                'location',
                'payments',
                'logs.admin',
                'line_items',
                'tax_lines',
                'discount',
            ]),
            'message' => 'Order has been updated successfully!',
        ], 200);
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json([
            'message' => 'Order has been deleted successfully!',
        ], 200);
    }

    public function destroySelected(Request $request, Order $order)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $order->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });
        return response()->json([
            'message' => trans_modules('destroy', 'order'),
        ], 200);
    }

    public function restore($id)
    {
        Order::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => 'Order has been restored successfully!',
        ], 200);
    }

    public function restoreSelected(Request $request, Order $order)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $order->onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => 'Orders has been restored successfully!',
        ], 200);
    }

    public function logs(Request $request, Order $order)
    {
        // Set rules
        $rules = [
            'message' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $comment = $order->logs()->create($request->input());

        // Update media
        if ($request->has('media')) {
            $comment->setMedia($request->input('media'));
        }

        return response()->json([
            'data' => $comment->load('admin', 'media'),
            'message' => 'Comment has been added successfully!',
        ], 200);
    }

    public function markAsPaid(Request $request, Order $order)
    {
        // Set rules
        $rules = [
            'payment_method' => 'required|exists:payment_methods,id',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $order->markAsPaid($request->payment_method, [
            'note' => 'Marked the manual payment as received'
        ]);

        return response()->json([
            'message' => 'Order manually marked as paid successfully!',
        ], 200);
    }

    public function duplicate(Order $order)
    {

        $order = $order->duplicate();

        return response()->json([
            'data' => $order->fresh([
                'discount',
                'logs.admin',
            ]),
            'message' => 'Order has been duplicated successfully!',
        ], 200);
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->is_cancelled) {
            abort(403, 'Order already canceled.');
        }

        $order->markAsCancelled($request->reason);

        if ($request->boolean('refund')) {
            $refund = $order->refunds()->create([
                'amount' => $order->fresh()->refundable_amount,
            ]);
            $order->markAsRefunded();
            event(new RefundProcessed($refund));
        }

        if ($request->boolean('restock')) {
            $order->restock();
        }

        return response()->json([
            'data' => $order->fresh([
                'discount',
                'logs.admin',
            ]),
            'message' => 'Order has been canceled successfully!',
        ], 200);
    }

    public function receipt(Order $order)
    {
        return $order->receiptPdf()->download("receipt-{$order->id}.pdf");
    }

    public function pos(Order $order)
    {
        return $order->posPdf()->download("receipt-{$order->id}.pdf");
    }

    public function sendInvoice(Request $request, Order $order)
    {
        $order->customer = new Customer([
            'id' => $order->customer?->id,
            'first_name' => $order->customer?->first_name,
            'last_name' => $order->customer?->last_name,
            'name' => $order->customer?->name,
            'email' => $request->to ?? $order->customer?->email,
        ]);

        // Send invoice notification to customer
        $order->customer->notify(new SendOrderInvoice($order));

        return response()->json([
            'message' => 'Invoice has been sent successfully!',
        ], 200);
    }
}
