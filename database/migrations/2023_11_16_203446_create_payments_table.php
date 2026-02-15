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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique()->nullable()->index();

            $table->string('paymentable_type')->nullable();
            $table->unsignedBigInteger('paymentable_id')->nullable();

            $table->unsignedBigInteger('payment_method_id')->index()->nullable();
            $table->string('transaction_id')->index()->nullable();
            $table->double('amount', 20, 2)->default(0.00);
            $table->double('fees', 10, 2)->nullable();
            $table->double('net_amount', 10, 2)->nullable();
            $table->double('refund_amount', 10, 2)->default(0);
            $table->boolean('capturable')->default(true);
            $table->string('status')->index()->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->cascadeOnDelete();

            $table->index(['paymentable_type', 'paymentable_id']);
        });

        $this->setAutoIncrement('payments');
    }
};
