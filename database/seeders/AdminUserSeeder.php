<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();

        if ($adminRole) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => 'admin@aarvixcms.local'],
                [
                    'name' => 'Super Admin',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Sync role to ensure no duplicates if run multiple times
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
