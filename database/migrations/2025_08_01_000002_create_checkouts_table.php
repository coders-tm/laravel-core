<?php

use Coderstm\Traits\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use Helpers;

    public function up()
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('type')->default('standard'); // 'standard' or 'subscription'
            $table->json('metadata')->nullable(); // Store subscription-specific data
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('session_id')->nullable();

            // Customer Information
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();

            // Billing Address
            $table->json('billing_address')->nullable();

            // Shipping Address (optional)
            $table->json('shipping_address')->nullable();
            $table->boolean('same_as_billing')->nullable()->default(true);

            // Cart snapshot at checkout time
            $table->string('coupon_code')->nullable();
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Payment Information
            $table->unsignedBigInteger('transaction_id')->nullable();

            // Notes
            $table->text('note')->nullable();
            $table->text('internal_note')->nullable();

            // Status and Timestamps
            $table->string('status')->default('draft'); // 'draft', 'pending', 'completed', 'abandoned', 'failed'
            $table->string('email_status')->nullable(); // 'not_sent', 'sent', 'failed'
            $table->string('recovery_status')->nullable(); // 'recovered', 'not_recovered'
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('recovery_email_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'session_id', 'status']);
            $table->index(['email', 'status']);
            $table->index(['started_at', 'status']);
        });

        $this->setAutoIncrement('checkouts');
    }

    public function down()
    {
        Schema::dropIfExists('checkouts');
    }
};
