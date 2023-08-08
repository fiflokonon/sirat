<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'first_name' => 'Arnaud',
                'last_name' => 'Fifonsi',
                'unique_id' => 'A6897',
                'balance' => 0,
                'email' => 'fifonsi@gmail.com',
                'password' => Hash::make('password'),
                'status' => true
            ]
        ]);
    }
}
