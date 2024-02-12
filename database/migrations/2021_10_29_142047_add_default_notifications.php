<?php

use Coderstm\Models\Notification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (notifications() as $notification) {
            Notification::updateOrCreate([
                'type' => $notification['type']
            ], $notification);
        }
    }
};
