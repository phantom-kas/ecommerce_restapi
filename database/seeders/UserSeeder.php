<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
          User::create([
            'name'     => 'Admin User',
            'email'    => 'kannyirisafana@outlook.com',
            'password' => '12345678',
            'role' =>'admin'
        ]);
    }
}
