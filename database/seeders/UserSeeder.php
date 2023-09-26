<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $startint = 90;

        DB::table('users')->insert([
            [
                'id'                    => $startint+1,
                'name'                  => 'test',
                'email'                 => 'test@gmail.com',
                'email_verified_at'   => date("Y-m-d H:i:s"),
                'password'              => Hash::make('password475'),
                'remember_token'        => str_random(10),
                'created_at'            => date("Y-m-d H:i:s"),
                'updated_at'            => date("Y-m-d H:i:s")
            ],[
                'id'                    => $startint+2,
                'name'                  => 'test',
                'email'                 => 'test@gmail.com',
                'email_verified_at'   => date("Y-m-d H:i:s"),
                'password'              => Hash::make('password475'),
                'remember_token'        => str_random(10),
                'created_at'            => date("Y-m-d H:i:s"),
                'updated_at'            => date("Y-m-d H:i:s")
            ]
        ]);
    }
}
