<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('number')->nullable();
            $table->string('currency')->nullable();
            $table->double('total', 15, 2)->nullable();
            $table->string('stripe_status')->nullable();
            $table->string('stripe_id')->nullable();
            $table->string('payment_intent')->nullable();
            $table->text('note')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
        });

        Schema::create('subscription_invoice_line_items', function (Blueprint $table) {
            $table->id();

            $table->text('description')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('stripe_id')->nullable();
            $table->string('stripe_price')->nullable();
            $table->string('stripe_plan')->nullable();
            $table->double('amount', 15, 2)->nullable()->default(0);
            $table->unsignedBigInteger('quantity')->nullable();
            $table->string('currency')->nullable();

            $table->foreign('invoice_id')->references('id')->on('subscription_invoices')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_invoice_line_items');
        Schema::dropIfExists('subscription_invoices');
    }
};
