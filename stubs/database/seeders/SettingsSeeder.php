<?php

namespace Database\Seeders;

use Coderstm\Traits\Helpers;
use Coderstm\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SettingsSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = json_decode(replace_short_code(file_get_contents(database_path('data/app-settings.json'))), true);

        foreach ($items as $item) {
            AppSetting::updateOrInsert([
                'key' => $item['key']
            ], $item);
        }
    }
}
