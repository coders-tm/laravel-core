<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminWalletTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = Admin::factory()->create([
            'is_supper_admin' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_user_wallet_balance()
    {
        $this->user->creditWallet(150.00, 'test', 'Test balance');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/users/{$this->user->id}/wallet/balance");

        $response->assertStatus(200)
            ->assertJson([
                'balance' => 150,
                'currency' => 'USD',
            ]);
    }

    #[Test]
    public function admin_can_view_user_wallet_transactions()
    {
        $this->user->creditWallet(100.00, 'test', 'First transaction');
        $this->user->creditWallet(50.00, 'test', 'Second transaction');
        $this->user->debitWallet(25.00, 'test', 'Third transaction');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/users/{$this->user->id}/wallet/transactions");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function admin_can_credit_user_wallet()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/users/{$this->user->id}/wallet/credit", [
                'amount' => 100.00,
                'description' => 'Admin credit',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Wallet credited successfully',
                'balance' => 100,
            ]);

        $this->assertEquals(100, $this->user->fresh()->getWalletBalance());
    }

    #[Test]
    public function admin_can_debit_user_wallet()
    {
        $this->user->creditWallet(200.00, 'test', 'Initial balance');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/users/{$this->user->id}/wallet/debit", [
                'amount' => 50.00,
                'description' => 'Admin debit',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Wallet debited successfully',
                'balance' => 150,
            ]);

        $this->assertEquals(150, $this->user->fresh()->getWalletBalance());
    }

    #[Test]
    public function admin_cannot_debit_more_than_wallet_balance()
    {
        $this->user->creditWallet(50.00, 'test', 'Initial balance');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/users/{$this->user->id}/wallet/debit", [
                'amount' => 100.00,
                'description' => 'Admin debit',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function admin_credit_requires_valid_amount()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/users/{$this->user->id}/wallet/credit", [
                'amount' => -50.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function admin_debit_requires_valid_amount()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/users/{$this->user->id}/wallet/debit", [
                'amount' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function non_admin_cannot_access_admin_wallet_routes()
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/users/{$this->user->id}/wallet/balance");

        $response->assertStatus(401);
    }
}
