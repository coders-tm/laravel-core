<?php

use Coderstm\Traits\Helpers;
use Coderstm\Models\PaymentMethod;
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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('provider')->default('manual')->index();
            $table->string('link')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->{$this->jsonable()}('credentials')->nullable();
            $table->{$this->jsonable()}('methods')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->string('capture')->default('manual')->index();
            $table->string('additional_details')->nullable();
            $table->string('payment_instructions')->nullable();
            $table->boolean('test_mode')->default(false);
            $table->decimal('transaction_fee', 10, 2)->default(0.00);
            $table->text('webhook')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('payment_methods');
    }
};
