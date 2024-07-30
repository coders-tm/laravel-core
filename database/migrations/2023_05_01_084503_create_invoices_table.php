<?php

use Coderstm\Traits\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use Helpers;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('key')->nullable()->unique();
            $table->string('number')->nullable()->unique();
            $table->string('status')->nullable();
            $table->string('currency')->nullable();
            $table->double('exchange_rate', 15, 4)->default(1);
            $table->double('sub_total', 15, 2)->nullable();
            $table->double('tax_total', 15, 2)->nullable();
            $table->double('discount_total', 15, 2)->nullable();
            $table->double('grand_total', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->{$this->jsonable()}('billing_address')->nullable();
            $table->boolean('collect_tax')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('subscription_invoice_line_items', function (Blueprint $table) {
            $table->id();

            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('quantity')->nullable();
            $table->double('price', 15, 2)->nullable()->default(0);
            $table->double('total', 15, 2)->nullable()->default(0);

            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('subscription_invoices')->cascadeOnUpdate()->cascadeOnDelete();
        });

        $this->setAutoIncrement('subscription_invoices');
        $this->setAutoIncrement('subscription_invoice_line_items');
    }
};
