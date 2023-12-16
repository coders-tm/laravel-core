<?php

namespace Coderstm\Database\Seeders;

use Coderstm\Database\Factories\Enquiry\ReplyFactory;
use Coderstm\Database\Factories\EnquiryFactory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EnquirySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EnquiryFactory::new()->count(10)
            ->has(
                ReplyFactory::new()
                    ->count(rand(0, 1))
            )
            ->create();
    }
}
