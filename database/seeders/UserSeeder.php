<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DebitCard;
use Illuminate\Database\Seeder;
use App\Models\DebitCardTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(3)->create()->each(function ($user) {
            (new ConsoleOutput())->writeln('user seeder' . json_encode($user));

            $cards = DebitCard::factory()->count(2)->for($user, 'user')->create();

            $cards->each(function ($card) {
                DebitCardTransaction::factory()->count(5)->for($card)->create();
            });
        });
    }
}
