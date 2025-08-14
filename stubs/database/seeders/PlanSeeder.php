<?php

namespace Database\Seeders;

use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Console\View\Components\TwoColumnDetail;
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
        $startTime = microtime(true);
        $rows = json_decode(file_get_contents(database_path('data/plans.json')), true);
        try {
            foreach ($rows as $item) {
                $features = $item['features'];
                unset($item['features']);
                $plan = Plan::create($item);

                $plan->syncFeatures($features);
            }
        } catch (\Exception $e) {
            report($e);
            $runTime = number_format((microtime(true) - $startTime) * 1000);
            with(new TwoColumnDetail($this->command->getOutput()))->render(
                $e->getMessage(),
                "<fg=gray>$runTime ms</> <fg=red;options=bold>ERROR</>"
            );

            $this->command->getOutput()->writeln('');
        }
    }
}
