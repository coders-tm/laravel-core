<?php

use Coderstm\Traits\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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

            $table->nullableMorphs('orderable');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->text('note')->nullable();
            $table->boolean('collect_tax')->default(true);
            $table->{$this->jsonable()}('attributes')->nullable();
            $table->string('source')->nullable();
            $table->string('key')->nullable();
            $table->string('currency')->nullable();
            $table->double('exchange_rate', 15, 4)->default(1);
            $table->double('sub_total', 20, 2)->default(0.00);
            $table->double('tax_total', 20, 2)->default(0.00);
            $table->double('discount_total', 20, 2)->default(0.00);
            $table->double('grand_total', 20, 2)->default(0.00);
            $table->dateTime('due_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('shop_locations')->nullOnDelete();
            $table->foreign('billing_address_id')->references('id')->on('addresses')->nullOnDelete();
        });

        Schema::create('line_items', function (Blueprint $table) {
            $table->id();

            $table->string('itemable_type')->nullable();
            $table->unsignedBigInteger('itemable_id')->nullable();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('title')->nullable();
            $table->string('variant_title')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('is_custom')->nullable()->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedBigInteger('accepted')->nullable();
            $table->unsignedBigInteger('rejected')->nullable();
            $table->unsignedBigInteger('quantity')->nullable();
            $table->{$this->jsonable()}('attributes')->nullable();
            $table->boolean('is_product_deleted')->nullable();
            $table->boolean('is_variant_deleted')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('variants')->nullOnDelete();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->double('amount', 20, 2)->default(0.00);
            $table->text('reason')->nullable();

            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnUpdate()->cascadeOnDelete();
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
