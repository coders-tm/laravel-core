<?php

namespace Coderstm\Tests\Feature;

use App\Models\Coupon;
use Coderstm\Models\User;
use Laravel\Sanctum\Sanctum;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;
use Coderstm\Http\Controllers\Subscription\SubscriptionController;

class SubscriptionControllerPest extends FeatureTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function defineRoutes($router)
    {
        $router->middleware(['auth:sanctum'])->group(function () use ($router) {
            // Subscription
            $router->group([
                'as' => 'subscription.',
                'prefix' => 'subscription',
                'controller' => SubscriptionController::class,
            ], function ()  use ($router) {
                $router->get('', 'index')->name('index');
                $router->get('setup-intent', 'getSetupIntent')->name('setup-intent');
                $router->post('subscribe', 'subscribe')->name('subscribe');
                $router->post('resume', 'resume')->name('resume');
                $router->post('confirm', 'confirm')->name('confirm');
                $router->post('pay', 'pay')->name('pay');
                $router->post('cancel-downgrade', 'cancelDowngrade')->name('cancel-downgrade');
                $router->post('invoices', 'invoices')->name('invoices');
            });
        });

        $router->post('subscription/check-promo-code', [SubscriptionController::class, 'checkPromoCode'])->name('subscription.check-promo-code');
    }

    public function it_can_get_subscription_index()
    {
        $response = $this->getJson(route('subscription.index'));

        $response->assertStatus(200);
    }

    public function it_can_subscribe_to_a_plan()
    {
        $plan = Plan::factory()->create();
        $paymentMethod = PaymentMethod::inRandomOrder()->first();

        $response = $this->postJson(route('subscription.subscribe'), [
            'plan' => $plan->id,
            'payment_method' => $paymentMethod->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);
    }

    // public function it_can_cancel_subscription()
    // {
    //     $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

    //     Notification::fake();

    //     $response = $this->postJson(route('subscription.cancel'));

    //     $response->assertStatus(200);
    //     Notification::assertSentTo($this->user, SubscriptionCancelNotification::class);
    // }

    // public function it_can_cancel_downgrade()
    // {
    //     $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

    //     $response = $this->postJson(route('subscription.cancelDowngrade'));

    //     $response->assertStatus(200);
    // }

    // public function it_can_resume_subscription()
    // {
    //     $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

    //     $response = $this->postJson(route('subscription.resume'));

    //     $response->assertStatus(200);
    // }

    // public function it_can_pay_for_subscription()
    // {
    //     $paymentMethod = PaymentMethod::inRandomOrder()->first();

    //     $response = $this->postJson(route('subscription.pay'), [
    //         'payment_method' => $paymentMethod->id,
    //     ]);

    //     $response->assertStatus(200);
    // }

    // public function it_can_get_invoices()
    // {
    //     $response = $this->getJson(route('subscription.invoices'));

    //     $response->assertStatus(200);
    // }

    // public function it_can_download_invoice()
    // {
    //     $invoice = Order::factory()->create();

    //     $response = $this->getJson(route('subscription.downloadInvoice', $invoice));

    //     $response->assertStatus(200);
    // }

    // public function it_can_check_promo_code()
    // {
    //     $plan = Plan::factory()->create();
    //     $coupon = Coupon::factory()->create();

    //     $response = $this->postJson(route('subscription.check-promo-code'), [
    //         'promotion_code' => $coupon->code,
    //         'plan_id' => $plan->id,
    //     ]);

    //     $response->assertStatus(200);
    // }
}
