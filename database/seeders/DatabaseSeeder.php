<?php

namespace Database\Seeders;

use Database\Factories\DebitCardFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory(10)->create();

        \App\Models\DebitCard::factory(50)->create();
        \App\Models\DebitCardTransaction::factory(100)->create();
        \App\Models\Loan::factory(20)->create();
        \App\Models\ScheduledRepayment::factory(50)->create();
    }
}
