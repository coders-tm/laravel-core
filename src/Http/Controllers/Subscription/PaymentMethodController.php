<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return response()->json($this->user()->getPaymentMethods());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required',
            'last_four_digit' => 'required',
        ]);

        $user = $this->user();
        $paymentMethod = $request->input('payment_method');

        if ($user->checkPaymentMethod($request->input('last_four_digit'))) {
            return abort(403, trans('coderstm::messages.payment_method.already'));
        }

        // create or get stripe customer
        $user->createOrGetStripeCustomer();

        if ($request->boolean('is_default')) {
            $user->updateDefaultPaymentMethod($paymentMethod);
            $user->updateDefaultPaymentMethodFromStripe();
        } else {
            $user->addPaymentMethod($paymentMethod);
        }

        return response()->json(['message' => trans('coderstm::messages.payment_method.success')]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $paymentMethod)
    {
        $user = $this->user();

        try {
            $user->updateDefaultPaymentMethod($paymentMethod);
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json(['message' => trans('coderstm::messages.payment_method.default')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $paymentMethod)
    {
        $user = $this->user();

        try {
            $paymentMethod = $user->findPaymentMethod($paymentMethod);
            $paymentMethod->delete();
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json(['message' => trans('coderstm::messages.payment_method.destory')]);
    }

    private function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }
        return current_user();
    }
}
