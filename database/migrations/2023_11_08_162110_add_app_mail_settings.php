<?php

use Coderstm\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        AppSetting::create('mail', [
            'default' => 'smtp',
            'mailers.smtp.host' => config('mail.mailers.smtp.host', '127.0.0.1'),
            'mailers.smtp.port' => config('mail.mailers.smtp.port', '1025'),
            'mailers.smtp.encryption' => config('mail.mailers.smtp.encryption', null),
            'mailers.smtp.username' => config('mail.mailers.smtp.username', null),
            'mailers.smtp.password' => config('mail.mailers.smtp.password', null),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
