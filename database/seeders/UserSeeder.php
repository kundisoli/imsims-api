<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@inventory.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'phone' => '+1234567890',
            'address' => '123 Admin Street, Admin City, AC 12345'
        ]);

        // Create manager user
        User::create([
            'name' => 'Inventory Manager',
            'email' => 'manager@inventory.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'phone' => '+1234567891',
            'address' => '456 Manager Avenue, Manager City, MC 12345'
        ]);

        // Create employee user
        User::create([
            'name' => 'Warehouse Employee',
            'email' => 'employee@inventory.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
            'phone' => '+1234567892',
            'address' => '789 Employee Road, Employee City, EC 12345'
        ]);

        // Create regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'user@inventory.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'phone' => '+1234567893',
            'address' => '321 User Lane, User City, UC 12345'
        ]);
    }
}