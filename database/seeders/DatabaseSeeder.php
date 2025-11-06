<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         $this->call([
            RolesandPermissionsSeeder::class,
            
        ]);

        $user = User::create([
         'name' => 'Admin',
         'email' => 'admin@example.com',
         'password' => bcrypt('12345678'),
        ]);
        $user->assignRole('Super Admin');

        $user = User::create([
         'name' => 'Developer',
         'email' => 'dev@example.com',
         'password' => bcrypt('dev1234'),
        ]);
        $user->assignRole('Developer');

        $user = User::create([
         'name' => 'Client',
         'email' => 'cli@example.com',
         'password' => bcrypt('cli1234'),
        ]);
        $user->assignRole('Client');
        
    }
}
