<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $paymentMethod = PaymentMethod::query();
        if ($request->boolean('enabled') ?: false) {
            $paymentMethod = $paymentMethod->enabled();
        }
        if ($request->boolean('disabled') ?: false) {
            $paymentMethod = $paymentMethod->disabled();
        }
        if ($request->boolean('manual') ?: false) {
            $paymentMethod = $paymentMethod->manual();
        }
        $paymentMethod = $paymentMethod->orderBy('order', 'asc')->get();

        return response()->json($paymentMethod, 200);
    }

    public function store(Request $request)
    {
        $rules = ['name' => 'required|string', 'provider' => 'required|string'];
        $request->validate($rules);
        $paymentMethod = PaymentMethod::create($request->input());

        return response()->json(['data' => $paymentMethod, 'message' => __('Payment method has been created successfully!')], 200);
    }

    public function show($paymentMethod)
    {
        $paymentMethod = PaymentMethod::findOrFail($paymentMethod);

        return response()->json($paymentMethod, 200);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $rules = ['name' => 'required|string', 'provider' => 'required|string'];
        $request->validate($rules);
        if ($paymentMethod->integration_via && ! config("{$paymentMethod->integration_via}.enabled", false)) {
            return response()->json(['message' => "This payment method is not enabled for integration. Please enable  {$paymentMethod->integration_via} first, then try again."], 422);
        }
        $paymentMethod->update($request->input());

        return response()->json(['data' => $paymentMethod->fresh(), 'message' => __('Payment method has been updated successfully!')], 200);
    }

    public function disable(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['active' => false]);

        return response()->json(['message' => __('Payment method has been disabled.')], 200);
    }

    public function enable(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['active' => true]);

        return response()->json(['message' => __('Payment method has been enabled.')], 200);
    }
}
