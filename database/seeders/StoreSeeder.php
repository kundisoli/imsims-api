<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        Store::truncate();

        Store::insert([
            [
                'name' => 'Central Market',
                'address' => '123 Main Street, Harare',
                'manager' => 'Alice Moyo',
                'phone' => '+263 77 123 4567',
                'email' => 'central@stores.com',
                'status' => 'active',
                'totalProducts' => 1200,
                'lowStockItems' => 15,
                'outOfStockItems' => 5,
                'monthlyRevenue' => 50000,
                'notes' => 'Main hub for distribution',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Westgate Branch',
                'address' => '45 Westgate, Harare',
                'manager' => 'Brian Chikore',
                'phone' => '+263 71 234 5678',
                'email' => 'westgate@stores.com',
                'status' => 'maintenance',
                'totalProducts' => 800,
                'lowStockItems' => 10,
                'outOfStockItems' => 3,
                'monthlyRevenue' => 30000,
                'notes' => 'Renovation scheduled next month',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bulawayo Depot',
                'address' => '88 Joshua Nkomo Street, Bulawayo',
                'manager' => 'Chipo Dube',
                'phone' => '+263 73 987 6543',
                'email' => 'bulawayo@stores.com',
                'status' => 'inactive',
                'totalProducts' => 600,
                'lowStockItems' => 20,
                'outOfStockItems' => 8,
                'monthlyRevenue' => 15000,
                'notes' => 'Currently closed due to low demand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
