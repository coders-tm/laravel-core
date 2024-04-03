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
        $notifications = json_decode(file_get_contents(database_path('data/notifications.json')), true);

        foreach ($notifications as $notification) {
            Notification::updateOrCreate([
                'type' => $notification['type']
            ], $notification);
        }
    }
};
