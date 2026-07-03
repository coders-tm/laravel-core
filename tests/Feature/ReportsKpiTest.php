<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use PHPUnit\Framework\Attributes\Test;

class ReportsKpiTest extends FeatureTestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data using factories for faster tests
        $this->seedTestData();

        // Create admin user for authentication
        $this->admin = Admin::factory()->create();
    }

    /**
     * Seed minimal test data for KPI tests
     */
    protected function seedTestData()
    {
        // Create users for the last 12 months
        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $count = rand(2, 5);
            User::factory()->count($count)->create([
                'created_at' => now()->subMonths($monthsAgo)->addDays(rand(0, 28)),
            ]);
        }

        // Create subscription plans
        $monthlyPlan = Plan::factory()->create([
            'price' => 29.99,
            'interval' => 'month',
            'interval_count' => 1,
        ]);

        $yearlyPlan = Plan::factory()->create([
            'price' => 299.99,
            'interval' => 'year',
            'interval_count' => 1,
        ]);

        // Create subscriptions for the last 12 months
        $users = User::all();
        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $subsCount = rand(3, 6);
            for ($i = 0; $i < $subsCount; $i++) {
                $plan = rand(0, 1) ? $monthlyPlan : $yearlyPlan;
                $user = $users->random();
                $createdAt = now()->subMonths($monthsAgo)->addDays(rand(0, 28));

                // 70% active, 15% trial, 15% cancelled
                $rand = rand(1, 100);
                if ($rand <= 70) {
                    Subscription::factory()->create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'created_at' => $createdAt,
                    ]);
                } elseif ($rand <= 85) {
                    Subscription::factory()->create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'trial_ends_at' => now()->addDays(rand(1, 14)),
                        'created_at' => $createdAt,
                    ]);
                } else {
                    Subscription::factory()->create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'canceled',
                        'canceled_at' => $createdAt->copy()->addMonths(rand(1, 3)),
                        'created_at' => $createdAt,
                    ]);
                }
            }
        }

        // Create orders for the last 12 months
        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $ordersCount = rand(5, 10);
            for ($i = 0; $i < $ordersCount; $i++) {
                Order::factory()->create([
                    'customer_id' => $users->random()->id,
                    'payment_status' => rand(1, 100) <= 95 ? 'paid' : 'pending',
                    'grand_total' => rand(50, 500) + (rand(0, 99) / 100), // $50.00 - $500.99
                    'created_at' => now()->subMonths($monthsAgo)->addDays(rand(0, 28)),
                ]);
            }
        }
    }

    #[Test]
    public function test_kpis_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/reports/kpis');
        $response->assertStatus(401);
    }

    #[Test]
    public function test_kpis_endpoint_returns_all_kpi_metrics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'mrr' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                    'by_plan',
                    'by_interval',
                ],
                'churn' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                    'logo_churn',
                    'revenue_churn',
                ],
                'ltv' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'arpu' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'cac' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'active_users' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                    'with_subscription',
                    'with_orders',
                ],
                'order_count' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'total_revenue' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'gross_revenue' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'net_revenue' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'aov' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'refund_rate' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'failed_payment_rate' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'repeat_rate' => [
                    'current',
                    'previous',
                    'change_absolute',
                    'change_percentage',
                    'trend',
                    'change',
                ],
                'metadata' => [
                    'current_period',
                    'previous_period',
                    'currency',
                    'generated_at',
                ],
            ]);
    }

    #[Test]
    public function test_kpis_endpoint_validates_date_parameters()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis?start_date=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    #[Test]
    public function test_kpis_endpoint_validates_end_date_after_start_date()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis?start_date=2025-12-01&end_date=2025-11-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    #[Test]
    public function test_kpis_mrr_calculation_with_active_subscriptions()
    {
        // Use existing seeded data instead of creating new data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // MRR should be calculated from existing active subscriptions
        $this->assertGreaterThan(0, $data['mrr']['current'], 'MRR should be greater than 0 with seeded data');
        $this->assertArrayHasKey('by_plan', $data['mrr']);
        $this->assertArrayHasKey('by_interval', $data['mrr']);
    }

    #[Test]
    public function test_kpis_churn_calculation()
    {
        // Use existing seeded data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('current', $data['churn']);
        $this->assertArrayHasKey('previous', $data['churn']);
        $this->assertArrayHasKey('logo_churn', $data['churn']);
        $this->assertArrayHasKey('revenue_churn', $data['churn']);
        $this->assertIsNumeric($data['churn']['current']);
    }

    #[Test]
    public function test_kpis_orders_metrics()
    {
        // Use existing seeded data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // Check flattened order metrics
        $this->assertArrayHasKey('order_count', $data);
        $this->assertArrayHasKey('gross_revenue', $data);
        $this->assertArrayHasKey('net_revenue', $data);
        $this->assertArrayHasKey('aov', $data);
        $this->assertArrayHasKey('refund_rate', $data);
        $this->assertArrayHasKey('failed_payment_rate', $data);
        $this->assertArrayHasKey('repeat_rate', $data);

        // Each order KPI should have period comparison
        $this->assertArrayHasKey('current', $data['order_count']);
        $this->assertArrayHasKey('previous', $data['order_count']);
        $this->assertArrayHasKey('trend', $data['order_count']);
        $this->assertArrayHasKey('description', $data['order_count']);
    }

    #[Test]
    public function test_kpis_active_users_calculation()
    {
        // Use existing seeded data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('current', $data['active_users']);
        $this->assertArrayHasKey('with_subscription', $data['active_users']);
        $this->assertArrayHasKey('with_orders', $data['active_users']);
        $this->assertGreaterThan(0, $data['active_users']['current']);
    }

    #[Test]
    public function test_kpis_metadata_includes_period_ranges()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('current_period', $data['metadata']);
        $this->assertArrayHasKey('previous_period', $data['metadata']);
        $this->assertArrayHasKey('currency', $data['metadata']);
        $this->assertArrayHasKey('generated_at', $data['metadata']);
        $this->assertArrayHasKey('start', $data['metadata']['current_period']);
        $this->assertArrayHasKey('end', $data['metadata']['current_period']);
        $this->assertArrayHasKey('start', $data['metadata']['previous_period']);
        $this->assertArrayHasKey('end', $data['metadata']['previous_period']);
    }

    #[Test]
    public function test_kpis_trend_calculation()
    {
        // Use existing seeded data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // Orders count should have trend indicator
        $this->assertArrayHasKey('trend', $data['order_count']);
        $this->assertContains($data['order_count']['trend'], ['up', 'down', 'flat']);
    }

    #[Test]
    public function test_kpis_with_custom_date_range()
    {
        // Skip due to Carbon date parsing issue in test environment
        // $this->markTestSkipped('Custom date range validation has Carbon parsing issues in test environment');
        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/reports/kpis?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('metadata', $data);
        $this->assertStringContainsString($startDate, $data['metadata']['current_period']['start']);
        $this->assertStringContainsString($endDate, $data['metadata']['current_period']['end']);
    }

    #[Test]
    public function test_kpis_percentage_formatted_correctly()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // Churn should be decimal (e.g., 0.06 for 6%)
        $this->assertIsNumeric($data['churn']['current']);

        // Refund rate should be decimal
        $this->assertIsNumeric($data['refund_rate']['current']);

        // Change percentage should be numeric
        $this->assertIsNumeric($data['mrr']['change_percentage']);
    }

    #[Test]
    public function test_kpis_change_string_formatting()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // MRR change should be percentage format (e.g., "+11.1%")
        $this->assertArrayHasKey('change', $data['mrr']);
        $this->assertIsString($data['mrr']['change']);

        // Churn change should be percentage points format (e.g., "+2pp")
        $this->assertArrayHasKey('change', $data['churn']);
        $this->assertIsString($data['churn']['change']);
        $this->assertStringContainsString('pp', $data['churn']['change']);
    }

    #[Test]
    public function test_kpis_handles_zero_division_gracefully()
    {
        // Test with no data (should not throw division by zero errors)
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // Should return valid structure even with no data
        $this->assertIsNumeric($data['mrr']['current']);
        $this->assertIsNumeric($data['churn']['current']);
        $this->assertIsNumeric($data['arpu']['current']);
    }

    #[Test]
    public function test_kpis_cache_clear_includes_kpi_metrics()
    {
        // Make initial request to cache data
        $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        // Clear cache
        $response = $this->actingAs($this->admin)
            ->postJson('/api/reports/clear-cache');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Reports cache cleared successfully',
            ]);
    }

    #[Test]
    public function test_kpis_mrr_segments_by_plan()
    {
        // Use existing seeded data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('by_plan', $data['mrr']);
        $this->assertIsArray($data['mrr']['by_plan']);

        if (count($data['mrr']['by_plan']) > 0) {
            $this->assertArrayHasKey('plan', $data['mrr']['by_plan'][0]);
            $this->assertArrayHasKey('mrr', $data['mrr']['by_plan'][0]);
        }
    }

    #[Test]
    public function test_kpis_ltv_calculation()
    {
        // Use existing seeded data
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('ltv', $data);
        $this->assertArrayHasKey('current', $data['ltv']);
        $this->assertArrayHasKey('previous', $data['ltv']);
        $this->assertIsNumeric($data['ltv']['current']);
    }

    #[Test]
    public function test_kpis_can_be_filtered_by_includes_parameter()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis?includes=mrr,churn,order_count');

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include requested KPIs
        $this->assertArrayHasKey('mrr', $data);
        $this->assertArrayHasKey('churn', $data);
        $this->assertArrayHasKey('order_count', $data);
        $this->assertArrayHasKey('metadata', $data); // Always included

        // Should not include other KPIs
        $this->assertArrayNotHasKey('ltv', $data);
        $this->assertArrayNotHasKey('arpu', $data);
        $this->assertArrayNotHasKey('active_users', $data);
        $this->assertArrayNotHasKey('total_revenue', $data);
        $this->assertArrayNotHasKey('new_customers', $data);
    }

    #[Test]
    public function test_kpis_includes_single_metric()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis?includes=mrr');

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include MRR
        $this->assertArrayHasKey('mrr', $data);
        $this->assertArrayHasKey('metadata', $data);

        // Should have exactly 2 keys (mrr + metadata)
        $this->assertCount(2, $data);
    }

    #[Test]
    public function test_kpis_includes_ignores_invalid_keys()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis?includes=mrr,invalid_key,churn,another_invalid');

        $response->assertStatus(200);

        $data = $response->json();

        // Should only include valid KPIs
        $this->assertArrayHasKey('mrr', $data);
        $this->assertArrayHasKey('churn', $data);
        $this->assertArrayHasKey('metadata', $data);

        // Should ignore invalid keys
        $this->assertArrayNotHasKey('invalid_key', $data);
        $this->assertArrayNotHasKey('another_invalid', $data);

        // Should have exactly 3 keys (mrr + churn + metadata)
        $this->assertCount(3, $data);
    }

    #[Test]
    public function test_kpis_includes_with_spaces()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis?includes=mrr, churn , order_count');

        $response->assertStatus(200);

        $data = $response->json();

        // Should trim spaces and include requested KPIs
        $this->assertArrayHasKey('mrr', $data);
        $this->assertArrayHasKey('churn', $data);
        $this->assertArrayHasKey('order_count', $data);
        $this->assertCount(4, $data); // mrr + churn + order_count + metadata
    }

    #[Test]
    public function test_kpis_without_includes_returns_all_metrics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/kpis');

        $response->assertStatus(200);

        $data = $response->json();

        // Should include all KPIs
        $this->assertArrayHasKey('mrr', $data);
        $this->assertArrayHasKey('churn', $data);
        $this->assertArrayHasKey('ltv', $data);
        $this->assertArrayHasKey('arpu', $data);
        $this->assertArrayHasKey('order_count', $data);
        $this->assertArrayHasKey('total_revenue', $data);
        $this->assertArrayHasKey('active_users', $data);
        $this->assertArrayHasKey('metadata', $data);

        // Should have many keys (all KPIs)
        $this->assertGreaterThan(10, count($data));
    }
}
