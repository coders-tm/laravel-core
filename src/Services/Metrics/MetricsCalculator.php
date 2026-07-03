<?php

namespace Coderstm\Services\Metrics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

abstract class MetricsCalculator
{
    protected Request $request;

    protected string $cachePrefix = 'metrics';

    protected int $cacheTTL = 3600; // 1 hour

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get cache key for metrics
     */
    protected function getCacheKey(string $metric): string
    {
        $filters = $this->request->only(['start_date', 'end_date', 'period']);
        $key = "{$this->cachePrefix}:{$metric}:".md5(json_encode($filters));

        return $key;
    }

    /**
     * Get or cache metric
     *
     * @return mixed
     */
    protected function remember(string $metric, callable $callback)
    {
        if ($this->request->boolean('no_cache')) {
            return $callback();
        }

        return Cache::remember(
            $this->getCacheKey($metric),
            $this->cacheTTL,
            $callback
        );
    }

    /**
     * Get date range from request
     *
     * @return array{start: Carbon, end: Carbon}
     */
    protected function getDateRange(): array
    {
        $start = $this->request->input('start_date', now()->subMonth()->startOfDay());
        $end = $this->request->input('end_date', now()->endOfDay());

        if (! $start instanceof Carbon) {
            $start = Carbon::parse($start);
        }

        if (! $end instanceof Carbon) {
            $end = Carbon::parse($end);
        }

        return [
            'start' => (clone $start)->startOfDay(),
            'end' => (clone $end)->endOfDay(),
        ];
    }

    /**
     * Get period from request (day, week, month, year)
     */
    protected function getPeriod(): string
    {
        return $this->request->input('period', 'month');
    }

    /**
     * Clear all metrics cache
     */
    public function clearCache(): void
    {
        Cache::tags([$this->cachePrefix])->flush();
    }

    /**
     * Format currency amount
     */
    protected function formatCurrency(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? config('app.currency', 'USD');

        return number_format($amount, 2).' '.strtoupper($currency);
    }

    /**
     * Get all metrics
     */
    /**
     * Optional metadata describing available filters/segments.
     */
    public function getMetadata(): array
    {
        return [
            'filters' => ['start_date', 'end_date', 'period', 'no_cache', 'compare'],
            'supports_compare' => $this->supportsComparison(),
        ];
    }

    /**
     * Return current metrics, optionally including comparison with previous period.
     */
    abstract public function get(): array;

    /**
     * Get current and previous period ranges derived from request.
     */
    protected function getComparisonPeriods(): array
    {
        $current = $this->getDateRange();
        $diffDays = $current['start']->diffInDays($current['end']);
        $previousEnd = (clone $current['start'])->subDay()->endOfDay();
        $previousStart = (clone $previousEnd)->subDays(max($diffDays, 0))->startOfDay();

        return [
            'current' => $current,
            'previous' => ['start' => $previousStart, 'end' => $previousEnd],
        ];
    }

    protected function supportsComparison(): bool
    {
        return true;
    }

    protected function shouldCompare(): bool
    {
        return $this->supportsComparison() && $this->request->boolean('compare');
    }

    /**
     * Convenience to compute a metric for a range via a callback returning float|int.
     */
    protected function computeForRange(callable $calculator, Carbon $start, Carbon $end)
    {
        return $calculator($start, $end);
    }

    /**
     * Format a comparison payload in a standardized way.
     */
    protected function formatComparison($current, $previous, string $type = 'number', array $additional = []): array
    {
        $delta = $current - $previous;

        return array_merge([
            'current' => $this->formatValue($current, $type),
            'previous' => $this->formatValue($previous, $type),
            'delta' => $this->formatValue($delta, $type),
            'delta_percent' => $previous == 0 ? null : round(($delta / $previous) * 100, 2),
            'trend' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
        ], $additional);
    }

    /**
     * Format primitive values consistently across metrics.
     */
    protected function formatValue($value, string $type)
    {
        return match ($type) {
            'currency' => $this->formatCurrency((float) $value),
            'percentage' => round((float) $value, 2),
            'number' => is_float($value) ? round($value, 2) : (int) $value,
            default => $value,
        };
    }

    /**
     * Attach comparison results to the payload in a standardized shape.
     *
     * @param  array<string,array{calculator: callable,type?:string,additional?:array|callable,description?:string}>  $comparisons
     */
    protected function withComparisons(array $payload, array $comparisons): array
    {
        if (! $this->shouldCompare() || empty($comparisons)) {
            return $payload;
        }

        $periods = $this->getComparisonPeriods();
        $payload['comparisons'] = [];

        foreach ($comparisons as $key => $definition) {
            $calculator = $definition['calculator'];
            $type = $definition['type'] ?? 'number';
            $additional = $definition['additional'] ?? [];
            $description = $definition['description'] ?? null;

            $current = $this->computeForRange($calculator, $periods['current']['start'], $periods['current']['end']);
            $previous = $this->computeForRange($calculator, $periods['previous']['start'], $periods['previous']['end']);

            $comparisonData = $this->formatComparison(
                $current,
                $previous,
                $type,
                is_callable($additional) ? $additional($periods) : $additional
            );

            // Add description if provided
            if ($description) {
                $comparisonData['description'] = $description;
            }

            $payload['comparisons'][$key] = $comparisonData;
        }

        if (! isset($payload['metadata'])) {
            $payload['metadata'] = $this->getMetadata();
        }

        $payload['metadata']['comparison_periods'] = $this->formatComparisonPeriods($periods);

        return $payload;
    }

    protected function formatComparisonPeriods(array $periods): array
    {
        return [
            'current' => [
                'start' => $periods['current']['start']->toIso8601String(),
                'end' => $periods['current']['end']->toIso8601String(),
            ],
            'previous' => [
                'start' => $periods['previous']['start']->toIso8601String(),
                'end' => $periods['previous']['end']->toIso8601String(),
            ],
        ];
    }
}
