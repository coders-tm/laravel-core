<?php

namespace Database\Seeders;

use Coderstm\Models\Tax;
use League\ISO3166\ISO3166;
use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ($country = $this->country()) {
            Tax::updateOrCreate([
                'country' => config('app.country', 'United States'),
                'label' => 'VAT',
                'code' => $country['alpha2'],
                'state' => '*',
                'rate' => 10,
                'priority' => 0,
            ]);
        }

        Tax::updateOrCreate([
            'country' => __('Rest of world'),
            'label' => 'VAT',
            'code' => '*',
            'state' => '*',
            'rate' => 0,
            'priority' => 0,
        ]);
    }

    protected function country()
    {
        return (new ISO3166)->name(config('app.country', 'United States'));
    }
}
