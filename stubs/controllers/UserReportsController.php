<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Coderstm\Services\SubscriptionReports;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Subscription;

class UserReportsController extends Controller
{
    public function index(Request $request)
    {
        $reports = new SubscriptionReports($request);

        switch ($request->type) {
            case 'rolling':
                $reports = $reports->onlyRolling();
                break;
            case 'end_date':
                $reports = $reports->onlyEnds();
                break;
            case 'cancelled':
                $reports = $reports->onlyCancelled();
                break;
            case 'free':
                $reports = $reports->onlyFree();
                break;
            default:
                $reports = $reports->query();
                break;
        }

        $reports = $reports->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);

        return new ResourceCollection($reports);
    }

    public function reports(Request $request)
    {
        $reports = new SubscriptionReports($request);

        $rolling = $reports->onlyRolling();
        $end_date = $reports->onlyEnds();
        $cancelled = $reports->onlyCancelled();

        return response()->json([
            'total' => $reports->count(),
            'total_paid' => $reports->sum('total_paid'),
            'rolling' => $rolling->count(),
            'rolling_total' => $rolling->sum('plan_price'),
            'end_date' => $end_date->count(),
            'end_date_total' => $end_date->sum('plan_price'),
            'free' => $reports->onlyFree($request)->count(),
            'cancelled' => $cancelled->count(),
            'cancelled_total' => $cancelled->sum('plan_price'),
        ], 200);
    }

    public function reportsMonthly(Request $request)
    {
        return $this->reports($request);
    }

    public function reportsYearly(Request $request)
    {
        return $this->reports($request);
    }

    public function reportsDaily(Request $request)
    {
        return $this->reports($request);
    }

    public function pdf(Request $request)
    {
        return Pdf::loadView('pdfs.reports', $request->only(['rows', 'columns']))->download("reports-{$request->type}.pdf");
    }
}
