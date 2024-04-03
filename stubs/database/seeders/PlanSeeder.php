<?php

namespace Database\Seeders;

use Coderstm\Models\Plan;
use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PlanSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = json_decode(file_get_contents(database_path('data/plans.json')), true);

        foreach ($plans as $item) {
            $plan = Plan::create($item);
            $plan->syncFeatures($item['features']);
        }
    }
}
