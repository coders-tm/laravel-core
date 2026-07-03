<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Retention\MrrChurnReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MrrChurnReportTest extends TestCase
{
    public function test_report_generates_mrr_churn_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(2)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 50.00, 'interval' => 'month']);

        DB::table('subscriptions')->insert([
            [
                'user_id' => 1001,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'cancelled',
                'quantity' => 1,
                'canceled_at' => $from->copy()->addDays(15)->toDateTimeString(),
                'created_at' => $from->copy()->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new MrrChurnReport;
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
