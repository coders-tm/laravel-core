<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Revenue\MrrByPlanReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MrrByPlanReportTest extends TestCase
{
    public function test_report_generates_mrr_by_plan_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan1 = Plan::factory()->create(['label' => 'Basic', 'price' => 30.00, 'interval' => 'month']);
        $plan2 = Plan::factory()->create(['label' => 'Pro', 'price' => 50.00, 'interval' => 'month']);

        DB::table('subscriptions')->insert([
            [
                'user_id' => 1001,
                'plan_id' => $plan1->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->toDateTimeString(),
            ],
            [
                'user_id' => 1002,
                'plan_id' => $plan2->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new MrrByPlanReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
}
