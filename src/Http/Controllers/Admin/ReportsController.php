<?php

namespace Coderstm\Http\Controllers\Admin;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Services\Charts\ChartService;
use Coderstm\Services\Metrics\CustomerMetrics;
use Coderstm\Services\Metrics\KpiMetrics;
use Coderstm\Services\Metrics\OrderMetrics;
use Coderstm\Services\Metrics\SubscriptionMetrics;
use Coderstm\Services\Reports\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function charts(Request $request): JsonResponse
    {
        $request->validate(['type' => 'required|in:revenue,subscriptions,customers,orders,mrr,churn,revenue-breakdown,members-breakdown', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date', 'period' => 'nullable|in:day,week,month,year', 'granularity' => 'nullable|in:daily,weekly,monthly,yearly']);
        $chartService = new ChartService($request);
        $chartData = match ($request->input('type')) {
            'revenue' => $chartService->getRevenueChart(),
            'subscriptions' => $chartService->getSubscriptionChart(),
            'customers' => $chartService->getCustomerChart(),
            'orders' => $chartService->getOrderChart(),
            'mrr' => $chartService->getMrrChart(),
            'churn' => $chartService->getChurnChart(),
            'revenue-breakdown' => $chartService->getRevenueBreakdown(),
            'members-breakdown' => $chartService->getMembersBreakdown(),
            default => [],
        };

        return response()->json($chartData);
    }

    public function types(): JsonResponse
    {
        return response()->json(['types' => ReportService::allWithLabels(), 'grouped' => ReportService::grouped()]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $validated = $request->validate(['category' => 'required|in:revenue,retention,economics,customers', 'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from', 'compare' => 'nullable|boolean', 'no_cache' => 'nullable|boolean']);
        if ($request->filled('date_from') && ! $request->filled('start_date')) {
            $request->merge(['start_date' => $validated['date_from']]);
        }
        if ($request->filled('date_to') && ! $request->filled('end_date')) {
            $request->merge(['end_date' => $validated['date_to']]);
        }
        $metrics = match ($validated['category']) {
            'revenue' => $this->getRevenueMetrics($request),
            'retention' => $this->getRetentionMetrics($request),
            'economics' => $this->getEconomicsMetrics($request),
            'customers' => $this->getCustomerMetrics($request),
            default => [],
        };

        return response()->json($metrics);
    }

    public function kpis(Request $request): JsonResponse
    {
        $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date', 'period' => 'nullable|in:day,week,month,year', 'no_cache' => 'nullable|boolean', 'includes' => 'nullable|string']);
        $kpiMetrics = new KpiMetrics($request);
        if ($request->filled('includes')) {
            $includes = array_map('trim', explode(',', $request->input('includes')));

            return response()->json($kpiMetrics->only($includes));
        }

        return response()->json($kpiMetrics->get());
    }

    public function clearCache(Request $request): JsonResponse
    {
        (new SubscriptionMetrics($request))->clearCache();
        (new OrderMetrics($request))->clearCache();
        (new CustomerMetrics($request))->clearCache();
        (new KpiMetrics($request))->clearCache();

        return response()->json(['message' => 'Reports cache cleared successfully']);
    }

    protected function getRevenueMetrics(Request $request): array
    {
        $subscriptionMetrics = new SubscriptionMetrics($request);
        $orderMetrics = new OrderMetrics($request);

        return $this->mergeMetrics($subscriptionMetrics->get(), $orderMetrics->get());
    }

    protected function getRetentionMetrics(Request $request): array
    {
        $subscriptionMetrics = new SubscriptionMetrics($request);

        return $subscriptionMetrics->get();
    }

    protected function getEconomicsMetrics(Request $request): array
    {
        $subscriptionMetrics = new SubscriptionMetrics($request);
        $customerMetrics = new CustomerMetrics($request);

        return $this->mergeMetrics($subscriptionMetrics->get(), $customerMetrics->get());
    }

    protected function getCustomerMetrics(Request $request): array
    {
        $customerMetrics = new CustomerMetrics($request);

        return $customerMetrics->get();
    }

    protected function mergeMetrics(array ...$metricSets): array
    {
        $merged = [];
        foreach ($metricSets as $set) {
            $comparisons = $set['comparisons'] ?? [];
            $metadata = $set['metadata'] ?? [];
            unset($set['comparisons'], $set['metadata']);
            $merged = array_merge($merged, $set);
            if (! empty($comparisons)) {
                $merged['comparisons'] = array_merge($merged['comparisons'] ?? [], $comparisons);
            }
            if (! empty($metadata)) {
                $merged['metadata'] = array_merge($merged['metadata'] ?? [], $metadata);
            }
        }

        return $merged;
    }
}
