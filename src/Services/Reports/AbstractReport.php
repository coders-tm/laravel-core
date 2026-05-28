<?php

namespace Coderstm\Services\Reports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Enum\PlanInterval;
use Coderstm\Models\ReportExport;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Traits\DatabaseAgnostic;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

abstract class AbstractReport implements ReportInterface
{
    use DatabaseAgnostic;

    protected ReportExport $reportExport;

    protected array $filters = [];

    protected Writer $csv;

    protected array $columns = [];

    abstract public static function getType(): string;

    public function headers(): array
    {
        $headers = [];
        foreach ($this->columns as $key => $column) {
            $headers[$key] = $column['label'];
        }

        return $headers;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function getDescription(): string
    {
        return 'Generate a detailed report for analysis';
    }

    public function validate(array $input): array
    {
        $rules = ['date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from', 'granularity' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly', 'status' => 'nullable|string', 'limit' => 'nullable|integer|min:1|max:100000'];
        $validated = validator($input, $rules)->validate();

        return $this->parseFilters($validated);
    }

    abstract public function query(array $filters);

    abstract public function toRow($row): array;

    public static function getTypes(): array
    {
        return [static::getType()];
    }

    public static function canHandle(string $type): bool
    {
        return $type === static::getType();
    }

    public function generate(Writer $csv, ReportExport $reportExport): int
    {
        $this->csv = $csv;
        $this->setReportExport($reportExport);
        $filters = $this->parseFilters($this->filters);
        $this->validateFilters($filters);
        $this->csv->insertOne(array_values($this->headers()));
        $totalRecords = 0;
        $this->stream($filters, function (array $row) use (&$totalRecords) {
            $this->csv->insertOne($this->orderRow($row));
            $totalRecords++;
        });

        return $totalRecords;
    }

    private function parseFilters(array $input): array
    {
        return ['from' => isset($input['date_from']) ? Carbon::parse($input['date_from'])->startOfDay() : now()->subDays(30)->startOfDay(), 'to' => isset($input['date_to']) ? Carbon::parse($input['date_to'])->endOfDay() : now()->endOfDay(), 'granularity' => $input['granularity'] ?? 'monthly', 'status' => $input['status'] ?? null, 'plan_id' => $input['plan_id'] ?? null, 'limit' => isset($input['limit']) ? (int) $input['limit'] : null];
    }

    private function validateFilters(array $filters): void
    {
        if ($filters['from'] instanceof \DateTimeInterface && $filters['to'] instanceof \DateTimeInterface) {
            if ($filters['from'] > $filters['to']) {
                throw new \InvalidArgumentException('Invalid date range: from date must be before to date');
            }
        }
    }

    public function stream(array $filters, callable $consume): void
    {
        $query = $this->query($filters);
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $query->limit($filters['limit']);
        }
        foreach ($query->cursor() as $row) {
            $consume($this->toRow($row));
        }
    }

    public function paginate(array $filters, int $perPage = 15, int $page = 1): array
    {
        $query = $this->query($filters);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $data = $paginator->getCollection()->map(function ($item) {
            return $this->toRow($item);
        })->values()->all();
        $summary = $this->summarize($filters);

        return ['data' => $data, 'meta' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'from' => $paginator->firstItem(), 'to' => $paginator->lastItem()], 'summary' => $summary, 'columns' => $this->columns()];
    }

    public function generateFromArray(array $items, callable $consume): void
    {
        foreach ($items as $item) {
            $consume($this->toRow($item));
        }
    }

    protected function orderRow(array $row): array
    {
        $ordered = [];
        foreach (array_keys($this->headers()) as $key) {
            $ordered[] = $row[$key] ?? null;
        }

        return $ordered;
    }

    public function summarize(array $filters): array
    {
        return [];
    }

    protected function beforeStream(array $filters): void {}

    protected function afterStream(array $filters, int $totalRecords): void {}

    public function setReportExport(ReportExport $reportExport): self
    {
        $this->reportExport = $reportExport;
        $this->filters = $reportExport->filters ?? [];

        return $this;
    }

    protected function getFilters(): array
    {
        return $this->filters;
    }

    protected function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    protected function getDatePeriods(): CarbonPeriod
    {
        $granularity = $this->getFilter('granularity', 'monthly');
        $dateFrom = $this->getFilter('date_from') ? Carbon::parse($this->getFilter('date_from')) : Carbon::now()->subMonths(12)->startOfMonth();
        $dateTo = $this->getFilter('date_to') ? Carbon::parse($this->getFilter('date_to')) : Carbon::now();
        $interval = match ($granularity) {
            'daily' => '1 day',
            'weekly' => '1 week',
            'quarterly' => '3 months',
            'yearly' => '1 year',
            default => '1 month',
        };

        return CarbonPeriod::create($dateFrom, $interval, $dateTo);
    }

    protected function formatPeriodLabel(Carbon $date): string
    {
        $granularity = $this->getFilter('granularity', 'monthly');

        return match ($granularity) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('Y-\\WW'),
            'quarterly' => $date->format('Y-').'Q'.$date->quarter,
            'yearly' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    protected function getPeriodEnd(Carbon $periodStart): Carbon
    {
        $granularity = $this->getFilter('granularity', 'monthly');

        return match ($granularity) {
            'daily' => (clone $periodStart)->endOfDay(),
            'weekly' => (clone $periodStart)->addWeek()->subDay()->endOfDay(),
            'quarterly' => (clone $periodStart)->addQuarter()->subDay()->endOfDay(),
            'yearly' => (clone $periodStart)->addYear()->subDay()->endOfDay(),
            default => (clone $periodStart)->addMonth()->subDay()->endOfDay(),
        };
    }

    protected function money($value, ?string $currency = null): string
    {
        return $this->formatMoney($value, $currency);
    }

    protected function date($value, string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDate($value, $format);
    }

    protected function formatMoney($value, ?string $currency = null): string
    {
        return format_amount((float) ($value ?? 0), $currency);
    }

    protected function formatDate($value, string $format = 'Y-m-d H:i:s'): ?string
    {
        if (! $value) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        return Carbon::parse($value)->format($format);
    }

    protected function formatNumber(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', '');
    }

    protected function formatPercentage(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', '');
    }

    protected function formatBoolean($value): string
    {
        return $value ? 'Yes' : 'No';
    }

    protected function toString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return (string) $value;
    }

    protected function getMonthlyPrice(Plan $plan): float
    {
        $price = $plan->price;

        return match ($plan->interval) {
            PlanInterval::YEAR => $price / 12,
            PlanInterval::WEEK => $price * 4.345,
            PlanInterval::DAY => $price * 30,
            default => $price / ($plan->interval_count ?? 1),
        };
    }

    protected function calculateMrrAtDate(Carbon $date): float
    {
        $mrr = 0;
        $subscriptions = DB::table('subscriptions')->where('created_at', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('canceled_at')->orWhere('canceled_at', '>', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', $date);
        })->get();
        foreach ($subscriptions as $subscription) {
            $plan = Plan::find($subscription->plan_id);
            if ($plan) {
                $monthlyPrice = $this->getMonthlyPrice($plan);
                $mrr += $monthlyPrice * ($subscription->quantity ?? 1);
            }
        }

        return $mrr;
    }

    protected function buildSubscriptionQuery()
    {
        $query = DB::table('subscriptions');
        $status = $this->getFilter('status');
        if ($status) {
            if ($status === 'active') {
                $query->whereNull('canceled_at')->where('status', SubscriptionStatus::ACTIVE)->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            } elseif ($status === 'canceled') {
                $query->whereNotNull('canceled_at');
            }
        }
        if ($this->getFilter('date_from')) {
            $query->whereDate('created_at', '>=', $this->getFilter('date_from'));
        }
        if ($this->getFilter('date_to')) {
            $query->whereDate('created_at', '<=', $this->getFilter('date_to'));
        }

        return $query;
    }

    protected function buildUserQuery()
    {
        $query = Coderstm::$userModel::query();
        if ($this->getFilter('status')) {
            $query->where('status', $this->getFilter('status'));
        }
        if ($this->getFilter('date_from')) {
            $query->whereDate('created_at', '>=', $this->getFilter('date_from'));
        }
        if ($this->getFilter('date_to')) {
            $query->whereDate('created_at', '<=', $this->getFilter('date_to'));
        }

        return $query;
    }

    protected function buildOrdersQuery()
    {
        $query = DB::table('orders');
        if ($this->getFilter('status')) {
            $query->where('status', $this->getFilter('status'));
        }
        if ($this->getFilter('date_from')) {
            $query->whereDate('created_at', '>=', $this->getFilter('date_from'));
        }
        if ($this->getFilter('date_to')) {
            $query->whereDate('created_at', '<=', $this->getFilter('date_to'));
        }

        return $query;
    }

    protected function buildPaymentsQuery()
    {
        $query = DB::table('payments');
        if ($this->getFilter('status')) {
            $query->where('status', $this->getFilter('status'));
        }
        if ($this->getFilter('date_from')) {
            $query->whereDate('created_at', '>=', $this->getFilter('date_from'));
        }
        if ($this->getFilter('date_to')) {
            $query->whereDate('created_at', '<=', $this->getFilter('date_to'));
        }

        return $query;
    }

    protected function buildCheckoutsQuery()
    {
        $query = DB::table('checkouts');
        if ($this->getFilter('status')) {
            $query->where('status', $this->getFilter('status'));
        }
        if ($this->getFilter('date_from')) {
            $query->whereDate('started_at', '>=', $this->getFilter('date_from'));
        }
        if ($this->getFilter('date_to')) {
            $query->whereDate('started_at', '<=', $this->getFilter('date_to'));
        }

        return $query;
    }

    protected function getFilteredPlans()
    {
        $query = Plan::query();
        $planIds = $this->getFilter('plan_id');
        if (! empty($planIds)) {
            $query->whereIn('id', (array) $planIds);
        }

        return $query->get();
    }
}
