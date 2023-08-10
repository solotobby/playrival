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
        $users = [
            ['username' => 'solotob', 'email' => 'solotobby@gmail.com', 'phone' => '08137331282', 'password' => bcrypt('solomon001')],
            ['username' => 'dammy', 'email' => 'dammy@gmail.com', 'phone' => '08166219698', 'password' => bcrypt('solomon001')],
            ['username' => 'kelvin', 'email' => 'kevin@gmail.com', 'phone' => '07059486166', 'password' => bcrypt('solomon001')]
        ];

        foreach($users as $user){
            $user = User::updateOrCreate($user);
            $user->assignRole('admin');
        }
    }
}
