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
        Schema::create('tax_lines', function (Blueprint $table) {
            $table->id();

            $table->string('taxable_type')->nullable();
            $table->unsignedBigInteger('taxable_id')->nullable();

            $table->string('label');
            $table->enum('type', ['normal', 'harmonized', 'compounded'])->default('normal');
            $table->decimal('rate', 5, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);

            $table->index(['taxable_type', 'taxable_id']);
        });

        $this->setAutoIncrement('tax_lines');

        Schema::create('discount_lines', function (Blueprint $table) {
            $table->id();

            $table->string('discountable_type')->nullable();
            $table->unsignedBigInteger('discountable_id')->nullable();

            $table->enum('type', ['percentage', 'fixed_amount'])->default('fixed_amount');
            $table->decimal('value', 20, 2)->default(0.00);
            $table->string('description')->nullable();

            $table->index(['discountable_type', 'discountable_id']);
        });

        $this->setAutoIncrement('discount_lines');
    }
};
