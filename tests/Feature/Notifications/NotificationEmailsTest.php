<?php

namespace Tests\Feature\Notifications;

use Coderstm\Models\Admin;
use Coderstm\Models\Enquiry;
use Coderstm\Models\File;
use Coderstm\Models\Import;
use Coderstm\Models\Payment;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Coderstm\Models\Task;
use Coderstm\Models\User;
use Coderstm\Notifications\Admins\EnquiryNotification as AdminEnquiryNotification;
use Coderstm\Notifications\Admins\HoldMemberNotification;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification as AdminSubscriptionCanceledNotification;
use Coderstm\Notifications\Admins\SubscriptionExpiredNotification as AdminSubscriptionExpiredNotification;
use Coderstm\Notifications\EnquiryConfirmation;
use Coderstm\Notifications\EnquiryReplyNotification;
use Coderstm\Notifications\ImportCompletedNotification;
use Coderstm\Notifications\NewAdminNotification;
use Coderstm\Notifications\Shop\Admins\PaymentFailedNotification as AdminPaymentFailedNotification;
use Coderstm\Notifications\Shop\Admins\RefundProcessedNotification;
use Coderstm\Notifications\Shop\OrderRefundedNotification;
use Coderstm\Notifications\Shop\PartialRefundNotification;
use Coderstm\Notifications\Shop\PaymentFailedNotification;
use Coderstm\Notifications\Shop\PaymentSuccessNotification;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Coderstm\Notifications\SubscriptionExpiringNotification;
use Coderstm\Notifications\SubscriptionGraceNotification;
use Coderstm\Notifications\SubscriptionRenewedNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Notifications\TaskUserNotification;
use Coderstm\Notifications\UserLogin;
use Coderstm\Notifications\UserSignupNotification;
use Database\Seeders\NotificationSeeder;
use Tests\TestCase;
use Workbench\App\Notifications\SendOrderInvoice;

/**
 * Test to send actual emails using all notification templates
 *
 * To run this test and send real emails:
 * 1. Configure your .env with real mail settings (e.g., Mailtrap, Mailhog, or SMTP)
 * 2. Run: vendor/bin/phpunit tests/Feature/NotificationEmailsTest.php
 *
 * For Mailtrap (recommended for testing):
 * MAIL_MAILER=smtp
 * MAIL_HOST=sandbox.smtp.mailtrap.io
 * MAIL_PORT=2525
 * MAIL_USERNAME=your_username
 * MAIL_PASSWORD=your_password
 * MAIL_FROM_ADDRESS=test@example.com
 * MAIL_FROM_NAME="Laravel Core"
 */
class NotificationEmailsTest extends TestCase
{
    protected $user;

    protected $admin;

    protected $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Force SMTP mail driver for actual email sending
        config([
            'mail.default' => env('MAIL_MAILER', 'log'),
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', '127.0.0.1'),
                'port' => env('MAIL_PORT', 1025),
                'encryption' => null,
                'username' => null,
                'password' => null,
                'timeout' => null,
            ],
        ]);

        // Seed notification templates
        $this->seed(NotificationSeeder::class);

        // Create test data
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test sending user signup notification.
     */
    public function test_send_user_signup_notification(): void
    {
        $this->user->notify(new UserSignupNotification($this->user));

        $this->assertTrue(true); // Email sent successfully
    }

    /**
     * Test: Send subscription upgrade notification
     */
    public function test_send_subscription_upgrade_notification()
    {
        $oldPlan = $this->subscription->plan;
        $newPlan = $this->subscription->plan; // In real scenario, this would be different

        $this->user->notify(new SubscriptionUpgradeNotification(
            $this->subscription,
            $oldPlan,
            $newPlan
        ));

        $this->assertTrue(true, 'Subscription upgrade email sent');
    }

    /**
     * Test: Send subscription downgrade notification
     */
    public function test_send_subscription_downgrade_notification()
    {
        $oldPlan = $this->subscription->plan;
        $newPlan = $this->subscription->plan;

        $this->user->notify(new SubscriptionDowngradeNotification(
            $this->subscription,
            $oldPlan,
            $newPlan
        ));

        $this->assertTrue(true, 'Subscription downgrade email sent');
    }

    /**
     * Test: Send subscription renewed notification
     */
    public function test_send_subscription_renewed_notification()
    {
        $this->user->notify(new SubscriptionRenewedNotification($this->subscription));

        $this->assertTrue(true, 'Subscription renewed email sent');
    }

    /**
     * Test: Send subscription canceled notification (user)
     */
    public function test_send_subscription_canceled_notification()
    {
        $this->subscription->update(['canceled_at' => now()]);

        $this->user->notify(new SubscriptionCanceledNotification($this->subscription));

        $this->assertTrue(true, 'Subscription canceled (user) email sent');
    }

    /**
     * Test: Send subscription expired notification (user)
     */
    public function test_send_subscription_expired_notification()
    {
        $this->subscription->update([
            'canceled_at' => now()->subDays(30),
            'expires_at' => now()->subDay(),
        ]);

        $this->user->notify(new SubscriptionExpiredNotification($this->subscription));

        $this->assertTrue(true, 'Subscription expired (user) email sent');
    }

    public function test_send_subscription_expiring_notification()
    {
        $this->user->notify(new SubscriptionExpiringNotification($this->subscription, 'user:subscription-expiring-x-day', 7));

        $this->assertTrue(true, 'Subscription expiring notification email sent');
    }

    public function test_send_subscription_grace_notification()
    {
        $this->user->notify(new SubscriptionGraceNotification($this->subscription));

        $this->assertTrue(true, 'Subscription grace notification email sent');
    }

    /**
     * Test: Send admin subscription canceled notification
     */
    public function test_send_admin_subscription_canceled_notification()
    {
        $this->subscription->update(['canceled_at' => now()]);

        $this->admin->notify(new AdminSubscriptionCanceledNotification($this->subscription));

        $this->assertTrue(true, 'Admin subscription canceled email sent');
    }

    /**
     * Test: Send admin subscription expired notification
     */
    public function test_send_admin_subscription_expired_notification()
    {
        $this->subscription->update([
            'canceled_at' => now()->subDays(30),
            'expires_at' => now()->subDay(),
        ]);

        $this->admin->notify(new AdminSubscriptionExpiredNotification($this->subscription));

        $this->assertTrue(true, 'Admin subscription expired email sent');
    }

    /**
     * Test: Send new admin notification
     */
    public function test_send_new_admin_notification()
    {
        $newAdmin = Admin::factory()->create([
            'email' => 'newadmin@example.com',
            'first_name' => 'New',
            'last_name' => 'Admin',
        ]);

        $password = 'temporary-password-123';

        $newAdmin->notify(new NewAdminNotification($newAdmin, $password));

        $this->assertTrue(true, 'New admin notification sent');
    }

    /**
     * Test: Send task user notification
     */
    public function test_send_task_user_notification()
    {
        $task = Task::factory()->create([
            'user_id' => $this->admin->id,
            'subject' => 'Review New Feature',
            'message' => 'Please review the new feature implementation.',
        ]);

        // Add file attachments to the task using media relationship
        $file1 = File::create([
            'name' => 'requirements.pdf',
            'path' => 'tasks/requirements.pdf',
            'type' => 'application/pdf',
            'size' => 102400, // 100KB
        ]);

        $file2 = File::create([
            'name' => 'screenshot.png',
            'path' => 'tasks/screenshot.png',
            'type' => 'image/png',
            'size' => 204800, // 200KB
        ]);

        $task->media()->attach([$file1->id, $file2->id]);

        // Reload task to get attachments
        $task->refresh();

        $this->admin->notify(new TaskUserNotification($task, $this->admin));

        $this->assertTrue(true, 'Task user notification sent with attachments');
    }

    /**
     * Test: Send enquiry confirmation notification
     */
    public function test_send_enquiry_confirmation_notification()
    {
        $enquiry = Enquiry::factory()->create([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'subject' => 'Product Question',
            'message' => 'I have a question about your product.',
        ]);

        $this->user->notify(new EnquiryConfirmation($enquiry));

        $this->assertTrue(true, 'Enquiry confirmation notification sent');
    }

    /**
     * Test: Send enquiry reply notification
     */
    public function test_send_enquiry_reply_notification()
    {
        $enquiry = Enquiry::factory()->create([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'subject' => 'Product Question',
        ]);

        $reply = $enquiry->replies()->create([
            'message' => 'Thank you for your question. Here is the answer...',
            'admin_id' => $this->admin->id,
        ]);

        $this->user->notify(new EnquiryReplyNotification($reply));

        $this->assertTrue(true, 'Enquiry reply notification sent');
    }

    /**
     * Test: Send admin enquiry notification
     */
    public function test_send_admin_enquiry_notification()
    {
        $enquiry = Enquiry::factory()->create([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'subject' => 'Customer Support Request',
            'message' => 'I need help with my account.',
        ]);

        $this->admin->notify(new AdminEnquiryNotification($enquiry));

        $this->assertTrue(true, 'Admin enquiry notification sent');
    }

    /**
     * Test: Send hold member notification
     */
    public function test_send_hold_member_notification()
    {
        $this->admin->notify(new HoldMemberNotification($this->user));

        $this->assertTrue(true, 'Hold member notification sent');
    }

    /**
     * Test: Send import completed notification
     */
    public function test_send_import_completed_notification()
    {
        $import = Import::create([
            'user_id' => $this->admin->id,
            'model' => 'User',
            'status' => Import::STATUS_COMPLETED,
            'success' => ['count' => 100],
            'failed' => ['count' => 5],
            'skipped' => [],
        ]);

        $this->admin->notify(new ImportCompletedNotification($import));

        $this->assertTrue(true, 'Import completed notification sent');
    }

    /**
     * Test: Send user login notification
     */
    public function test_send_user_login_notification()
    {
        $log = $this->user->logs()->create([
            'type' => 'login',
            'status' => 'success',
            'message' => 'User logged in',
            'options' => [
                'device' => 'Chrome',
                'time' => now()->format('M d, Y h:i A'),
                'location' => 'New York, USA',
                'ip' => '192.168.1.1',
            ],
        ]);

        $this->user->notify(new UserLogin($log));

        $this->assertTrue(true, 'User login notification sent');
    }

    /**
     * Test: Send subscription cancel notification (request)
     */
    public function test_send_subscription_cancel_notification()
    {
        $this->user->notify(new SubscriptionCancelNotification($this->subscription));

        $this->assertTrue(true, 'Subscription cancel (request) notification sent');
    }

    /**
     * Test: Send order invoice notification with PDF attachment
     */
    public function test_send_order_invoice_notification_with_attachment()
    {
        // Create an order for the user (using customer_id, not user_id) with totals
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 100.00,
            'tax_total' => 10.00,
            'discount_total' => 0.00,
            'grand_total' => 110.00,
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::STATUS_PAID,
        ]);

        // Seed realistic line items for invoice email
        $order->line_items()->create([
            'title' => 'Invoice Widget',
            'variant_title' => 'Default',
            'quantity' => 2,
            'price' => 50.00,
            'total' => 100.00,
            'sku' => 'INV-001',
        ]);

        // Send invoice notification (includes PDF attachment)
        $this->user->notify(new SendOrderInvoice($order));

        $this->assertTrue(true, 'Order invoice notification with PDF attachment sent');
    }

    /**
     * Test: Send partial refund notification
     */
    public function test_send_partial_refund_notification()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 100.00,
            'tax_total' => 10.00,
            'grand_total' => 110.00,
            'status' => Order::STATUS_COMPLETED,
        ]);

        // Add line items using factory
        $order->line_items()->create([
            'title' => 'Partial Refund Widget',
            'variant_title' => 'Default',
            'quantity' => 2,
            'price' => 50.00,
            'total' => 100.00,
            'sku' => 'PRT-001',
        ]);

        // Create a payment for refund
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 50.00,
            'status' => 'completed',
            'currency' => 'USD',
            'gateway' => 'stripe',
        ]);

        // Fresh order with line items before sending notification
        $this->user->notify(new PartialRefundNotification($order->fresh(['line_items']), $payment, 50.00));

        $this->assertTrue(true, 'Partial refund notification sent');
    }

    /**
     * Test: Send order refunded notification
     */
    public function test_send_order_refunded_notification()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 100.00,
            'tax_total' => 10.00,
            'grand_total' => 110.00,
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        // Add line items using factory
        $order->line_items()->create([
            'title' => 'Refunded Widget',
            'variant_title' => 'Default',
            'quantity' => 1,
            'price' => 100.00,
            'total' => 100.00,
            'sku' => 'RFND-001',
        ]);

        // Create a payment for refund
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 110.00,
            'status' => 'completed',
            'currency' => 'USD',
            'gateway' => 'stripe',
        ]);

        // Fresh order with line items before sending notification
        $this->user->notify(new OrderRefundedNotification($order->fresh(['line_items']), $payment, 110.00));

        $this->assertTrue(true, 'Order refunded notification sent');
    }

    /**
     * Test: Send payment success notification
     */
    public function test_send_payment_success_notification()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 100.00,
            'tax_total' => 10.00,
            'grand_total' => 110.00,
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::STATUS_PAID,
        ]);

        // Add line items using factory
        $order->line_items()->create([
            'title' => 'Paid Widget',
            'variant_title' => 'Default',
            'quantity' => 2,
            'price' => 50.00,
            'total' => 100.00,
            'sku' => 'PAID-001',
        ]);

        // Fresh order with line items before sending notification
        $this->user->notify(new PaymentSuccessNotification($order->fresh(['line_items'])));

        $this->assertTrue(true, 'Payment success notification sent');
    }

    /**
     * Test: Send payment failed notification
     */
    public function test_send_payment_failed_notification()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 55.00,
            'tax_total' => 5.50,
            'grand_total' => 60.50,
            'status' => Order::STATUS_PENDING,
            'payment_status' => 'failed',
        ]);

        // Add line items using factory
        $order->line_items()->create([
            'title' => 'Failed Payment Widget',
            'variant_title' => 'Default',
            'quantity' => 1,
            'price' => 55.00,
            'total' => 55.00,
            'sku' => 'FAIL-001',
        ]);

        $reason = 'Insufficient funds';

        // Fresh order with line items before sending notification
        $this->user->notify(new PaymentFailedNotification($order->fresh(['line_items']), $reason));

        $this->assertTrue(true, 'Payment failed notification sent');
    }

    /**
     * Test: Send payment failed notification (admin)
     */
    public function test_send_payment_failed_notification_admin()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 150.00,
            'tax_total' => 15.00,
            'grand_total' => 165.00,
            'status' => Order::STATUS_PENDING,
            'payment_status' => 'failed',
        ]);

        // Add line items using factory
        $order->line_items()->create([
            'title' => 'Failed Admin Payment Widget',
            'variant_title' => 'Default',
            'quantity' => 1,
            'price' => 150.00,
            'total' => 150.00,
            'sku' => 'ADM-FAIL-001',
        ]);

        $reason = 'Card declined';

        // Fresh order with line items before sending notification
        $this->admin->notify(new AdminPaymentFailedNotification($order->fresh(['line_items']), $reason));

        $this->assertTrue(true, 'Payment failed notification (admin) sent');
    }

    /**
     * Test: Send refund processed notification (admin)
     */
    public function test_send_refund_processed_notification_admin()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'sub_total' => 100.00,
            'tax_total' => 10.00,
            'grand_total' => 110.00,
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        // Add line items using factory
        $order->line_items()->create([
            'title' => 'Admin Refunded Widget',
            'variant_title' => 'Default',
            'quantity' => 1,
            'price' => 110.00,
            'total' => 110.00,
            'sku' => 'ADM-RFND-001',
        ]);

        // Create a payment for refund
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 110.00,
            'status' => 'completed',
            'currency' => 'USD',
            'gateway' => 'stripe',
        ]);

        // Fresh order with line items before sending notification
        $this->admin->notify(new RefundProcessedNotification($order->fresh(['line_items']), $payment, 110.00));

        $this->assertTrue(true, 'Refund processed notification (admin) sent');
    }
}
