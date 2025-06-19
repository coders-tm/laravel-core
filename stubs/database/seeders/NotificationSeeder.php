<?php

namespace Database\Seeders;

use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Coderstm\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NotificationSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notifications = json_decode(file_get_contents(database_path('data/notifications.json')), true);

        foreach ($notifications as $notification) {
            Notification::updateOrCreate([
                'type' => $notification['type']
            ], $notification);
        }
    }
}
