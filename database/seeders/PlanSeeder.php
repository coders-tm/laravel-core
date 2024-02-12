<?php

namespace Coderstm\Database\Seeders;

use Coderstm\Models\Plan;
use Illuminate\Support\Arr;
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
        $rows = json_decode('[{"label":"Free","description":"Max <strong>0\/mo<\/strong> Guest pass\nMax <strong>2\/mo<\/strong> Classes\nFull Access to the Facilities\nClasses and Online Portal","monthly_fee":0,"yearly_fee":0,"classes":2,"guest":0},{"label":"Basic","description":"Max <strong>2\/mo<\/strong> Guest pass\nMax <strong>15\/mo<\/strong> Classes\nFull Access to the Facilities\nClasses and Online Portal","monthly_fee":4.99,"yearly_fee":49.9,"classes":15,"guest":2},{"label":"Pro","description":"Max <strong>5\/mo<\/strong> Guest pass\nMax <strong>20\/mo<\/strong> Classes\nFull Access to the Facilities\nClasses and Online Portal","monthly_fee":9.99,"yearly_fee":99.9,"classes":20,"guest":5}]', true);

        foreach ($rows as $item) {
            $plan = Plan::create($item);

            $plan->syncFeatures(collect([
                [
                    'label' => 'Classes',
                    'description' => 'Maximum classes can be booked and join.',
                    'value' => $item['classes']
                ],
                [
                    'label' => 'Guest pass',
                    'description' => 'Allows non-members to try out the studio facilities',
                    'value' => $item['guest']
                ]
            ]));
        }
    }
}
