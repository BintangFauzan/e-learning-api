<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Dr. Budi Dosen',
            'email' => 'dosen@kampus.com',
            'password' => Hash::make('password'),
            'role' => 'dosen',
        ]);

        // Mahasiswa
        User::create([
            'name' => 'Ahmad Mahasiswa',
            'email' => 'mahasiswa@kampus.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);
    }
}
