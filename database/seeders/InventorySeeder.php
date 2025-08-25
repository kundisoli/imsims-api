<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PostgreSQL\Category;
use App\Models\PostgreSQL\Supplier;
use App\Models\PostgreSQL\Warehouse;
use App\Models\PostgreSQL\Location;
use App\Models\PostgreSQL\Product;
use App\Models\PostgreSQL\InventoryRecord;
use App\Models\MongoDB\ProductMetadata;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $electronics = Category::create([
            'name' => 'Electronics',
            'description' => 'Electronic devices and accessories',
            'is_active' => true,
            'sort_order' => 1
        ]);

        $computers = Category::create([
            'name' => 'Computers',
            'description' => 'Desktop and laptop computers',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'sort_order' => 1
        ]);

        $accessories = Category::create([
            'name' => 'Accessories',
            'description' => 'Computer accessories',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'sort_order' => 2
        ]);

        $clothing = Category::create([
            'name' => 'Clothing',
            'description' => 'Apparel and clothing items',
            'is_active' => true,
            'sort_order' => 2
        ]);

        // Create suppliers
        $techSupplier = Supplier::create([
            'name' => 'Tech Solutions Inc.',
            'company_name' => 'Tech Solutions Incorporated',
            'email' => 'orders@techsolutions.com',
            'phone' => '+1-555-0001',
            'address' => '123 Tech Street',
            'city' => 'Silicon Valley',
            'state' => 'California',
            'postal_code' => '94000',
            'country' => 'United States',
            'tax_number' => 'TAX123456789',
            'payment_terms' => 'Net 30',
            'credit_limit' => 50000.00,
            'is_active' => true
        ]);

        $apparelSupplier = Supplier::create([
            'name' => 'Fashion Forward Ltd.',
            'company_name' => 'Fashion Forward Limited',
            'email' => 'orders@fashionforward.com',
            'phone' => '+1-555-0002',
            'address' => '456 Fashion Avenue',
            'city' => 'New York',
            'state' => 'New York',
            'postal_code' => '10001',
            'country' => 'United States',
            'tax_number' => 'TAX987654321',
            'payment_terms' => 'Net 15',
            'credit_limit' => 25000.00,
            'is_active' => true
        ]);

        // Create warehouses
        $mainWarehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN-001',
            'description' => 'Primary distribution center',
            'address' => '789 Warehouse Blvd',
            'city' => 'Distribution City',
            'state' => 'Texas',
            'postal_code' => '75001',
            'country' => 'United States',
            'manager_id' => 2, // Manager user ID
            'is_active' => true
        ]);

        $secondaryWarehouse = Warehouse::create([
            'name' => 'Secondary Warehouse',
            'code' => 'SEC-001',
            'description' => 'Secondary storage facility',
            'address' => '321 Storage Lane',
            'city' => 'Storage City',
            'state' => 'Florida',
            'postal_code' => '33001',
            'country' => 'United States',
            'manager_id' => 2, // Manager user ID
            'is_active' => true
        ]);

        // Create locations
        $locations = [
            ['name' => 'A1-01', 'code' => 'A1-01', 'type' => 'shelf', 'aisle' => 'A', 'rack' => '1', 'shelf' => '01'],
            ['name' => 'A1-02', 'code' => 'A1-02', 'type' => 'shelf', 'aisle' => 'A', 'rack' => '1', 'shelf' => '02'],
            ['name' => 'B1-01', 'code' => 'B1-01', 'type' => 'shelf', 'aisle' => 'B', 'rack' => '1', 'shelf' => '01'],
            ['name' => 'B1-02', 'code' => 'B1-02', 'type' => 'shelf', 'aisle' => 'B', 'rack' => '1', 'shelf' => '02'],
        ];

        foreach ($locations as $locationData) {
            Location::create(array_merge($locationData, [
                'warehouse_id' => $mainWarehouse->id,
                'is_active' => true,
                'capacity' => 100,
                'temperature_controlled' => false,
                'hazardous_materials' => false
            ]));

            Location::create(array_merge($locationData, [
                'warehouse_id' => $secondaryWarehouse->id,
                'is_active' => true,
                'capacity' => 100,
                'temperature_controlled' => false,
                'hazardous_materials' => false
            ]));
        }

        // Create products
        $products = [
            [
                'name' => 'Dell OptiPlex 7090',
                'description' => 'High-performance desktop computer for business use',
                'sku' => 'DELL-OPT-7090',
                'barcode' => '1234567890001',
                'category_id' => $computers->id,
                'supplier_id' => $techSupplier->id,
                'cost_price' => 899.99,
                'selling_price' => 1299.99,
                'min_stock_level' => 5,
                'max_stock_level' => 50,
                'reorder_point' => 10,
                'unit_of_measure' => 'piece',
                'weight' => 12.5,
                'dimensions' => ['length' => 30, 'width' => 20, 'height' => 35],
                'is_active' => true,
                'is_trackable' => true
            ],
            [
                'name' => 'HP EliteBook 840',
                'description' => 'Professional laptop with advanced security features',
                'sku' => 'HP-ELITE-840',
                'barcode' => '1234567890002',
                'category_id' => $computers->id,
                'supplier_id' => $techSupplier->id,
                'cost_price' => 1199.99,
                'selling_price' => 1699.99,
                'min_stock_level' => 3,
                'max_stock_level' => 30,
                'reorder_point' => 8,
                'unit_of_measure' => 'piece',
                'weight' => 2.8,
                'dimensions' => ['length' => 35, 'width' => 25, 'height' => 2.5],
                'is_active' => true,
                'is_trackable' => true
            ],
            [
                'name' => 'Logitech MX Master 3',
                'description' => 'Advanced wireless mouse for professionals',
                'sku' => 'LOG-MX-MASTER3',
                'barcode' => '1234567890003',
                'category_id' => $accessories->id,
                'supplier_id' => $techSupplier->id,
                'cost_price' => 59.99,
                'selling_price' => 99.99,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'reorder_point' => 20,
                'unit_of_measure' => 'piece',
                'weight' => 0.15,
                'dimensions' => ['length' => 12, 'width' => 8, 'height' => 4],
                'is_active' => true,
                'is_trackable' => true
            ],
            [
                'name' => 'Cotton T-Shirt',
                'description' => '100% cotton comfortable t-shirt',
                'sku' => 'COTTON-TSHIRT-001',
                'barcode' => '1234567890004',
                'category_id' => $clothing->id,
                'supplier_id' => $apparelSupplier->id,
                'cost_price' => 9.99,
                'selling_price' => 24.99,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'reorder_point' => 50,
                'unit_of_measure' => 'piece',
                'weight' => 0.2,
                'dimensions' => ['length' => 30, 'width' => 20, 'height' => 1],
                'is_active' => true,
                'is_trackable' => true
            ],
            [
                'name' => 'Wireless Keyboard',
                'description' => 'Ergonomic wireless keyboard with backlight',
                'sku' => 'WIRELESS-KB-001',
                'barcode' => '1234567890005',
                'category_id' => $accessories->id,
                'supplier_id' => $techSupplier->id,
                'cost_price' => 39.99,
                'selling_price' => 79.99,
                'min_stock_level' => 15,
                'max_stock_level' => 75,
                'reorder_point' => 25,
                'unit_of_measure' => 'piece',
                'weight' => 0.8,
                'dimensions' => ['length' => 45, 'width' => 15, 'height' => 3],
                'is_active' => true,
                'is_trackable' => true
            ]
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            // Create inventory records
            $mainLocation = Location::where('warehouse_id', $mainWarehouse->id)->first();
            $secondaryLocation = Location::where('warehouse_id', $secondaryWarehouse->id)->first();

            // Main warehouse inventory
            $mainQuantity = rand(15, 45);
            InventoryRecord::create([
                'product_id' => $product->id,
                'warehouse_id' => $mainWarehouse->id,
                'location_id' => $mainLocation->id,
                'quantity' => $mainQuantity,
                'reserved_quantity' => rand(0, 5),
                'available_quantity' => $mainQuantity - rand(0, 5),
                'last_counted_at' => now()->subDays(rand(1, 30)),
                'last_movement_at' => now()->subDays(rand(1, 7))
            ]);

            // Secondary warehouse inventory
            $secondaryQuantity = rand(5, 25);
            InventoryRecord::create([
                'product_id' => $product->id,
                'warehouse_id' => $secondaryWarehouse->id,
                'location_id' => $secondaryLocation->id,
                'quantity' => $secondaryQuantity,
                'reserved_quantity' => rand(0, 3),
                'available_quantity' => $secondaryQuantity - rand(0, 3),
                'last_counted_at' => now()->subDays(rand(1, 30)),
                'last_movement_at' => now()->subDays(rand(1, 7))
            ]);

            // Create product metadata in MongoDB
            $metadata = [
                'product_id' => $product->id,
                'tags' => $this->getProductTags($product->name),
                'images' => [
                    [
                        'id' => uniqid(),
                        'url' => "https://via.placeholder.com/300x300?text=" . urlencode($product->name),
                        'alt' => $product->name,
                        'is_primary' => true,
                        'uploaded_at' => now()->toISOString()
                    ]
                ],
                'specifications' => $this->getProductSpecifications($product->name),
                'attributes' => [
                    'brand' => $this->getProductBrand($product->name),
                    'model' => $this->getProductModel($product->name),
                    'color' => $this->getRandomColor()
                ],
                'seo_data' => [
                    'meta_title' => $product->name . ' - Best Price Online',
                    'meta_description' => $product->description,
                    'keywords' => implode(', ', $this->getProductTags($product->name))
                ],
                'custom_fields' => [
                    'warranty_period' => $this->getWarrantyPeriod($product->name),
                    'origin_country' => $this->getOriginCountry($product->name)
                ]
            ];

            ProductMetadata::create($metadata);
        }
    }

    private function getProductTags(string $productName): array
    {
        $tagMap = [
            'Dell OptiPlex 7090' => ['desktop', 'computer', 'business', 'dell', 'workstation'],
            'HP EliteBook 840' => ['laptop', 'business', 'hp', 'portable', 'professional'],
            'Logitech MX Master 3' => ['mouse', 'wireless', 'productivity', 'logitech', 'ergonomic'],
            'Cotton T-Shirt' => ['clothing', 'cotton', 'casual', 'apparel', 'comfortable'],
            'Wireless Keyboard' => ['keyboard', 'wireless', 'ergonomic', 'backlight', 'productivity']
        ];

        return $tagMap[$productName] ?? ['product', 'inventory'];
    }

    private function getProductSpecifications(string $productName): array
    {
        $specMap = [
            'Dell OptiPlex 7090' => [
                'processor' => 'Intel Core i7-11700',
                'memory' => '16GB DDR4',
                'storage' => '512GB SSD',
                'operating_system' => 'Windows 11 Pro'
            ],
            'HP EliteBook 840' => [
                'processor' => 'Intel Core i5-11350U',
                'memory' => '8GB DDR4',
                'storage' => '256GB SSD',
                'display' => '14" Full HD',
                'operating_system' => 'Windows 11 Pro'
            ],
            'Logitech MX Master 3' => [
                'connectivity' => 'Bluetooth, USB-C',
                'battery_life' => '70 days',
                'dpi' => '4000 DPI',
                'buttons' => '7 buttons'
            ],
            'Cotton T-Shirt' => [
                'material' => '100% Cotton',
                'sizes' => 'S, M, L, XL, XXL',
                'care_instructions' => 'Machine wash cold',
                'origin' => 'Made in USA'
            ],
            'Wireless Keyboard' => [
                'connectivity' => 'Bluetooth 5.0',
                'battery_life' => '6 months',
                'key_type' => 'Scissor switch',
                'backlight' => 'RGB LED'
            ]
        ];

        return $specMap[$productName] ?? [];
    }

    private function getProductBrand(string $productName): string
    {
        $brandMap = [
            'Dell OptiPlex 7090' => 'Dell',
            'HP EliteBook 840' => 'HP',
            'Logitech MX Master 3' => 'Logitech',
            'Cotton T-Shirt' => 'Generic',
            'Wireless Keyboard' => 'TechBrand'
        ];

        return $brandMap[$productName] ?? 'Unknown';
    }

    private function getProductModel(string $productName): string
    {
        $modelMap = [
            'Dell OptiPlex 7090' => 'OptiPlex 7090',
            'HP EliteBook 840' => 'EliteBook 840 G8',
            'Logitech MX Master 3' => 'MX Master 3',
            'Cotton T-Shirt' => 'Classic Tee',
            'Wireless Keyboard' => 'WK-2024'
        ];

        return $modelMap[$productName] ?? 'Standard';
    }

    private function getRandomColor(): string
    {
        $colors = ['Black', 'White', 'Silver', 'Blue', 'Red', 'Gray', 'Navy'];
        return $colors[array_rand($colors)];
    }

    private function getWarrantyPeriod(string $productName): string
    {
        $warrantyMap = [
            'Dell OptiPlex 7090' => '3 years',
            'HP EliteBook 840' => '3 years',
            'Logitech MX Master 3' => '2 years',
            'Cotton T-Shirt' => '30 days',
            'Wireless Keyboard' => '1 year'
        ];

        return $warrantyMap[$productName] ?? '1 year';
    }

    private function getOriginCountry(string $productName): string
    {
        $countryMap = [
            'Dell OptiPlex 7090' => 'USA',
            'HP EliteBook 840' => 'USA',
            'Logitech MX Master 3' => 'Switzerland',
            'Cotton T-Shirt' => 'USA',
            'Wireless Keyboard' => 'China'
        ];

        return $countryMap[$productName] ?? 'Unknown';
    }
}