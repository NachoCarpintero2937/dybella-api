<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // User::create([
        //     'name' => 'Administrador Test',
        //     'email' => 'admin@pigtureit.com',
        //     'password' => bcrypt('Q1w2e3r4!'),
        // ])->assignRole('Admin');

        // User::create([
        //     'name' => 'Adrian',
        //     'email' => 'admin@admin.com',
        //     'password' => bcrypt('Adrian1234'),
        // ])->assignRole('Admin');

        User::create([
            'name' => 'QR',
            'email' => 'qr@eventossv.com',
            'password' => bcrypt('Qr25032023'),
        ])->assignRole('Admin');

        // User::create([
        //     'name' => 'User',
        //     'email' => 'user@pigtureit.com',
        //     'password' => bcrypt('Q1w2e3r4!'),
        // ])->assignRole('User');
    }
}
