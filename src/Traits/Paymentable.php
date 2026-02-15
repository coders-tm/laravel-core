<?php

namespace Coderstm\Traits;

use Illuminate\Http\Request;

trait Paymentable
{
    public function success(Request $request)
    {
        $this->verifyPayment($request);

        return redirect($request->redirect ?? app_url('/billing'));
    }

    public function process(Request $request)
    {
        return response()->json($this->verifyPayment($request), 200);
    }
}
