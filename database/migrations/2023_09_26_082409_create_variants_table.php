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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();

            $table->decimal('price', 20, 2)->default(0.00);
            $table->decimal('compare_at_price', 20, 2)->default(0.00);
            $table->decimal('cost_per_item', 20, 2)->default(0.00);
            $table->boolean('taxable')->default(true);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('out_of_stock_track_inventory')->default(false);
            $table->string('sku')->nullable();
            $table->decimal('weight', 10, 3)->default(0.00);
            $table->string('weight_unit')->nullable()->default('kg');
            $table->string('origin')->nullable();
            $table->string('harmonized_system_code')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('is_default')->nullable()->default(false);
            $table->unsignedBigInteger('media_id')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('media_id')->references('id')->on('files')->nullOnDelete();

            $table->index(['product_id', 'media_id']);
        });

        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('option_id');
            $table->integer('position')->default(0);
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('variant_id')->references('id')->on('variants')->cascadeOnDelete();
            $table->foreign('option_id')->references('id')->on('options')->cascadeOnDelete();

            $table->index(['variant_id', 'option_id']);
        });

        $this->setAutoIncrement('variants');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variants');
    }
};
