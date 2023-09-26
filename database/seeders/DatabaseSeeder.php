<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        DB::table('users')->insert([
            'name' => 'test',
            'email' => 'test@gmail.com',
            'email_verified_at'   => date("Y-m-d H:i:s"),
            'password' => Hash::make('password'),
            'remember_token'        => Str::random(10),
        ]);
    }
}
