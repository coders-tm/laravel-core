<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Acquisition\TrialConversionReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrialConversionReportTest extends TestCase
{
    public function test_report_generates_trial_conversion_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['trial_days' => 14]);

        // Create trial subscriptions
        DB::table('subscriptions')->insert([
            [
                'user_id' => 1001,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'trial_ends_at' => $from->copy()->addDays(14)->toDateTimeString(),
                'created_at' => $from->copy()->toDateTimeString(),
                'canceled_at' => null,
            ],
            [
                'user_id' => 1002,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'cancelled',
                'quantity' => 1,
                'trial_ends_at' => $from->copy()->addDays(14)->toDateTimeString(),
                'created_at' => $from->copy()->addDays(1)->toDateTimeString(),
                'canceled_at' => $from->copy()->addDays(10)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new TrialConversionReport;
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

    public function test_summary_calculates_conversion_rate()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['trial_days' => 7]);

        DB::table('subscriptions')->insert([
            'user_id' => 1001,
            'plan_id' => $plan->id,
            'type' => 'app',
            'status' => 'active',
            'quantity' => 1,
            'trial_ends_at' => $from->copy()->addDays(7)->toDateTimeString(),
            'created_at' => $from->copy()->toDateTimeString(),
        ]);

        // Act
        $report = new TrialConversionReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertIsArray($summary);
    }
}
