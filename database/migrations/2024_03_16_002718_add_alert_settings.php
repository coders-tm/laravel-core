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
        AppSetting::create('alert', [
            'whatsapp' => false,
            'push' => false,
        ]);
    }
};
