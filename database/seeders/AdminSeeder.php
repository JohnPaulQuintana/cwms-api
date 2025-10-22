<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Warehouse Staff',
                'email' => 'staff@example.com',
                'role' => 'warehouse_staff',
            ],
            [
                'name' => 'Project Manager',
                'email' => 'manager@example.com',
                'role' => 'project_manager',
            ],
        ];

        foreach ($users as $userData) {
            if (!User::where('email', $userData['email'])->exists()) {
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password123'), // 🔐 Default password
                    'role' => $userData['role'],
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);

                $this->command->info("✅ {$userData['role']} account created: {$userData['email']}");
            } else {
                $this->command->info("ℹ️ {$userData['role']} already exists: {$userData['email']}");
            }
        }
    }
}
