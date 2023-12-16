<?php

namespace Coderstm\Database\Seeders;

use Coderstm\Database\Factories\AddressFactory;
use Coderstm\Database\Factories\AdminFactory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AdminFactory::new()->count(10)->create()->each(function ($user) {
            $user->updateOrCreateAddress(AddressFactory::new()->make()->toArray());
        });
    }
}
