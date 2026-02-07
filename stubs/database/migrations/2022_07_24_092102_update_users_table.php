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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->boolean('is_active')->nullable()->default(true)->after('remember_token');
            $table->boolean('is_enquiry')->nullable()->default(false)->after('is_active');
            $table->string('status')->nullable()->after('is_active')->default('Pending');
            $table->string('gender')->nullable()->after('name');
            $table->date('dob')->nullable()->after('gender');
            $table->string('rag')->nullable()->after('is_active');
            $table->string('username')->nullable()->after('name');
            $table->string('note')->nullable()->after('phone_number');
            $table->timestamp('trial_ends_at')->nullable()->after('email_verified_at');
            $table->boolean('is_free_forever')->nullable()->after('is_active');
            $table->string('currency')->nullable()->after('is_active')->default('USD');
            $table->softDeletes();
        });

        $this->setAutoIncrement('users');
    }
};
