<?php

namespace Database\Seeders;

use Coderstm\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $rows = [
            [
                'label' => 'Classes',
                'slug' => 'classes',
                'resetable' => true, // false; credit will not reset on subscription renewal
                'description' => 'Maximum classes can be booked and join.',
            ],
            [
                'label' => 'Guest pass',
                'slug' => 'guest-pass',
                'resetable' => true,
                'description' => 'Allows non-members to try out the gym or studio facilities',
            ]
        ];

        Feature::whereNotIn('slug', collect($rows)->map(function ($item) {
            return $item['slug'];
        }))->delete();

        foreach ($rows as $item) {
            Feature::updateOrCreate(['label' => $item['label']], $item);
        }
    }
}
