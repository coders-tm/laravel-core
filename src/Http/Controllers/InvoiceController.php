<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Traits\Helpers;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Http\Controllers\Controller;

class InvoiceController extends Controller
{
    use Helpers;

    public function invoices(Request $request)
    {
        $invoices = $this->user()
            ->invoices()
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $invoices->whereStatus($request->status);
        }

        $invoices = $invoices->paginate($request->rowsPerPage ?: 10);
        return response()->json($invoices, 200);
    }

    public function downloadInvoice(Request $request, Order $invoice)
    {
        return $invoice->load('line_items')->download();
    }

    protected function user()
    {
        if (request()->filled('user_id') && is_admin()) {
            return Coderstm::$userModel::findOrFail(request()->user_id);
        }

        return user();
    }
}
