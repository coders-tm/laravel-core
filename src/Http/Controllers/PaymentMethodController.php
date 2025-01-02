<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PaymentMethod $paymentMethod)
    {
        if ($request->boolean('enabled') ?: false) {
            $paymentMethod = $paymentMethod->enabled();
        }

        if ($request->boolean('disabled') ?: false) {
            $paymentMethod = $paymentMethod->disabled();
        }

        if ($request->boolean('manual') ?: false) {
            $paymentMethod = $paymentMethod->manual();
        }

        return response()->json($paymentMethod->get(), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'provider' => 'required|string',
        ];

        $this->validate($request, $rules);

        $paymentMethod = PaymentMethod::create($request->input());

        return response()->json([
            'data' => $paymentMethod,
            'message' => 'Payment method has been created successfully!',
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Coderstm\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json($paymentMethod, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $rules = [
            'name' => 'required|string',
            'provider' => 'required|string',
        ];

        $this->validate($request, $rules);

        $paymentMethod->update($request->input());

        return response()->json([
            'data' => $paymentMethod->fresh(),
            'message' => 'Payment method has been updated successfully!',
        ], 200);
    }

    /**
     * Disable the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update([
            'active' => false,
        ]);

        return response()->json([
            'message' => 'Payment method has been disabled.',
        ], 200);
    }

    /**
     * Enable the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update([
            'active' => true,
        ]);

        return response()->json([
            'message' => 'Payment method has been enabled.',
        ], 200);
    }
}
