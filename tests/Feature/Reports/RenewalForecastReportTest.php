<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Subscriptions\RenewalForecastReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenewalForecastReportTest extends TestCase
{
    public function test_report_generates_renewal_forecast_data()
    {
        // Arrange
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->addMonths(3)->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 60.00, 'interval' => 'month']);

        DB::table('subscriptions')->insert([
            [
                'user_id' => 1001,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->subMonths(2)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new RenewalForecastReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
}
