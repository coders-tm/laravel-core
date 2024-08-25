<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            FeatureSeeder::class,
            ModuleSeeder::class,
            SettingsSeeder::class,
            GroupSeeder::class,
            NotificationSeeder::class,
            PaymentMethodSeeder::class,
            PlanSeeder::class,
            TaxSeeder::class,
            PageSeeder::class,
        ]);
    }
}
