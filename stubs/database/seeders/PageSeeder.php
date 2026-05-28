<?php

namespace Database\Seeders;

use Coderstm\Models\Page;
use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     */
    public function run()
    {
        $pages = json_decode(file_get_contents(database_path('data/pages.json')), true);

        Page::insertOrIgnore($pages);
    }
}
