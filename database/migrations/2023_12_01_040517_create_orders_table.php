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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft')->index();

            $table->nullableMorphs('orderable');
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('checkout_id')->nullable()->index();
            $table->text('note')->nullable();
            $table->boolean('collect_tax')->default(true);
            $table->{$this->jsonable()}('billing_address')->nullable();
            $table->{$this->jsonable()}('shipping_address')->nullable();
            $table->{$this->jsonable()}('metadata')->nullable();
            $table->string('source')->nullable();
            $table->string('key')->nullable();
            $table->double('sub_total', 20, 2)->default(0.00);
            $table->double('tax_total', 20, 2)->default(0.00);
            $table->double('shipping_total', 10, 2)->default(0);
            $table->double('discount_total', 20, 2)->default(0.00);
            $table->double('grand_total', 20, 2)->default(0.00);
            $table->double('paid_total', 20, 2)->default(0.00);
            $table->double('refund_total', 20, 2)->default(0.00);
            $table->integer('line_items_quantity')->default(0);
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('payment_status')->default('pending');
            $table->string('tracking_number')->nullable();
            $table->string('tracking_company')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes()->index();
            $table->index(['status', 'shipped_at'], 'orders_status_shipped_at_index');
            $table->index(['payment_status', 'due_date'], 'orders_payment_status_due_date_index');
        });

        Schema::create('line_items', function (Blueprint $table) {
            $table->id();

            $table->string('itemable_type')->nullable();
            $table->unsignedBigInteger('itemable_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('variant_title')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('is_custom')->nullable()->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedSmallInteger('accepted')->nullable();
            $table->unsignedSmallInteger('rejected')->nullable();
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->{$this->jsonable()}('attributes')->nullable();
            $table->{$this->jsonable()}('metadata')->nullable();
            $table->boolean('is_product_deleted')->nullable();
            $table->boolean('is_variant_deleted')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('variants')->nullOnDelete();

            $table->index(['itemable_type', 'itemable_id']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('amount', 20, 2)->default(0.00);
            $table->text('reason')->nullable();
            $table->boolean('to_wallet')->default(false);
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->{$this->jsonable()}('metadata')->nullable();

            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
        });

        Schema::create('order_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contactable_type')->nullable();
            $table->unsignedBigInteger('contactable_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
        });

        $this->setAutoIncrement('orders');
        $this->setAutoIncrement('line_items');
        $this->setAutoIncrement('refunds');
        $this->setAutoIncrement('order_contacts');
    }
};
