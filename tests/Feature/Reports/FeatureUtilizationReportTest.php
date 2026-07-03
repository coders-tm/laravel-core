<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Plans\FeatureUtilizationReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeatureUtilizationReportTest extends TestCase
{
    public function test_report_generates_feature_usage_data()
    {
        // Arrange: create subscriptions with feature usage
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // Ensure clean tables
        DB::table('subscriptions')->truncate();
        DB::table('subscription_features')->truncate();

        // Create plan
        $plan = Plan::factory()->create(['label' => 'Premium Plan', 'price' => 99.00]);

        // Create subscriptions
        DB::table('subscriptions')->insert([
            [
                'id' => 1,
                'user_id' => 1001,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(1)->toDateTimeString(),
                'canceled_at' => null,
            ],
            [
                'id' => 2,
                'user_id' => 1002,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(2)->toDateTimeString(),
                'canceled_at' => null,
            ],
        ]);

        // Create subscription features with usage
        DB::table('subscription_features')->insert([
            [
                'subscription_id' => 1,
                'slug' => 'api-calls',
                'label' => 'API Calls',
                'type' => 'integer',
                'value' => 1000,
                'used' => 750,
                'created_at' => $from->copy()->addDays(1)->toDateTimeString(),
                'updated_at' => $from->copy()->addDays(1)->toDateTimeString(),
            ],
            [
                'subscription_id' => 2,
                'slug' => 'api-calls',
                'label' => 'API Calls',
                'type' => 'integer',
                'value' => 1000,
                'used' => 950,
                'created_at' => $from->copy()->addDays(2)->toDateTimeString(),
                'updated_at' => $from->copy()->addDays(2)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new FeatureUtilizationReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert: report generates without errors
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);

        // Verify data structure
        foreach ($result['data'] as $row) {
            $this->assertArrayHasKey('plan_id', $row);
            $this->assertArrayHasKey('plan_name', $row);
            $this->assertArrayHasKey('feature_name', $row);
            $this->assertArrayHasKey('feature_limit', $row);
            $this->assertArrayHasKey('avg_usage', $row);
            $this->assertArrayHasKey('utilization_rate', $row);
            $this->assertIsNumeric($row['feature_limit']);
            $this->assertIsNumeric($row['avg_usage']);
            $this->assertIsNumeric($row['utilization_rate']);
        }
    }

    public function test_summary_calculates_total_features()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();
        DB::table('subscription_features')->truncate();

        // Create plan
        $plan = Plan::factory()->create(['label' => 'Test Plan', 'price' => 50.00]);

        // Create subscription
        DB::table('subscriptions')->insert([
            'id' => 1,
            'user_id' => 1001,
            'plan_id' => $plan->id,
            'type' => 'app',
            'status' => 'active',
            'quantity' => 1,
            'created_at' => $from->copy()->toDateTimeString(),
            'canceled_at' => null,
        ]);

        // Create subscription features
        DB::table('subscription_features')->insert([
            [
                'subscription_id' => 1,
                'slug' => 'feature-1',
                'label' => 'Feature 1',
                'type' => 'integer',
                'value' => 100,
                'used' => 50,
                'created_at' => $from->copy()->toDateTimeString(),
                'updated_at' => $from->copy()->toDateTimeString(),
            ],
            [
                'subscription_id' => 1,
                'slug' => 'feature-2',
                'label' => 'Feature 2',
                'type' => 'integer',
                'value' => 200,
                'used' => 150,
                'created_at' => $from->copy()->toDateTimeString(),
                'updated_at' => $from->copy()->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new FeatureUtilizationReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertArrayHasKey('total_plans', $summary);
        $this->assertArrayHasKey('total_features_tracked', $summary);
    }
}
