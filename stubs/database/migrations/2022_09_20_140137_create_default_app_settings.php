<?php

use Coderstm\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $items = json_decode(file_get_contents(database_path('data/app-settings.json')), true);

        foreach ($items as $item) {
            AppSetting::updateOrInsert([
                'key' => $item['key']
            ], $item);
        }
    }
};
