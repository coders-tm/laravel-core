<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Traits\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceController extends Controller
{
    use Helpers;

    public function invoices(Request $request)
    {
        $query = $this->user($request)->invoices();
        if ($request->filled('status')) {
            $query->whereStatus($request->status);
        }
        $invoices = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?: 15);

        return new ResourceCollection($invoices);
    }

    public function downloadInvoice(Request $request, $invoice)
    {
        $invoice = Coderstm::$orderModel::findOrFail($invoice);
        if (is_user() && $invoice->customer_id !== user('id')) {
            abort(403, __('You are not authorized to access this invoice.'));
        }

        return $invoice->load('line_items')->download();
    }

    protected function user(Request $request)
    {
        if (is_admin()) {
            return Coderstm::$userModel::findOrFail($request->user);
        }

        return user();
    }
}
