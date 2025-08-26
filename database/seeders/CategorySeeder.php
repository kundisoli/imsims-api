<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and components',
                'is_active' => true,
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Computers',
                        'description' => 'Laptops, desktops, and computer accessories',
                        'is_active' => true,
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Laptops', 'description' => 'Portable computers', 'sort_order' => 1],
                            ['name' => 'Desktops', 'description' => 'Desktop computers', 'sort_order' => 2],
                            ['name' => 'Accessories', 'description' => 'Computer accessories', 'sort_order' => 3],
                        ]
                    ],
                    [
                        'name' => 'Mobile Devices',
                        'description' => 'Smartphones, tablets, and accessories',
                        'is_active' => true,
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Smartphones', 'description' => 'Mobile phones', 'sort_order' => 1],
                            ['name' => 'Tablets', 'description' => 'Tablet computers', 'sort_order' => 2],
                            ['name' => 'Mobile Accessories', 'description' => 'Phone and tablet accessories', 'sort_order' => 3],
                        ]
                    ],
                    [
                        'name' => 'Audio & Video',
                        'description' => 'Audio and video equipment',
                        'is_active' => true,
                        'sort_order' => 3,
                        'children' => [
                            ['name' => 'Headphones', 'description' => 'Audio headphones and earbuds', 'sort_order' => 1],
                            ['name' => 'Speakers', 'description' => 'Audio speakers and sound systems', 'sort_order' => 2],
                            ['name' => 'Cameras', 'description' => 'Digital cameras and camcorders', 'sort_order' => 3],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Home & Garden',
                'description' => 'Home improvement and garden supplies',
                'is_active' => true,
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Furniture',
                        'description' => 'Home and office furniture',
                        'is_active' => true,
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Living Room', 'description' => 'Living room furniture', 'sort_order' => 1],
                            ['name' => 'Bedroom', 'description' => 'Bedroom furniture', 'sort_order' => 2],
                            ['name' => 'Office', 'description' => 'Office furniture', 'sort_order' => 3],
                        ]
                    ],
                    [
                        'name' => 'Garden',
                        'description' => 'Garden tools and supplies',
                        'is_active' => true,
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Tools', 'description' => 'Garden tools and equipment', 'sort_order' => 1],
                            ['name' => 'Plants', 'description' => 'Plants and seeds', 'sort_order' => 2],
                            ['name' => 'Fertilizers', 'description' => 'Garden fertilizers and chemicals', 'sort_order' => 3],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Clothing & Accessories',
                'description' => 'Clothing, shoes, and fashion accessories',
                'is_active' => true,
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => 'Men\'s Clothing',
                        'description' => 'Men\'s apparel and accessories',
                        'is_active' => true,
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Shirts', 'description' => 'Men\'s shirts and tops', 'sort_order' => 1],
                            ['name' => 'Pants', 'description' => 'Men\'s pants and trousers', 'sort_order' => 2],
                            ['name' => 'Shoes', 'description' => 'Men\'s footwear', 'sort_order' => 3],
                        ]
                    ],
                    [
                        'name' => 'Women\'s Clothing',
                        'description' => 'Women\'s apparel and accessories',
                        'is_active' => true,
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Dresses', 'description' => 'Women\'s dresses', 'sort_order' => 1],
                            ['name' => 'Tops', 'description' => 'Women\'s tops and blouses', 'sort_order' => 2],
                            ['name' => 'Shoes', 'description' => 'Women\'s footwear', 'sort_order' => 3],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Sports & Recreation',
                'description' => 'Sports equipment and recreational items',
                'is_active' => true,
                'sort_order' => 4,
                'children' => [
                    [
                        'name' => 'Fitness',
                        'description' => 'Fitness and exercise equipment',
                        'is_active' => true,
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Cardio Equipment', 'description' => 'Treadmills, bikes, etc.', 'sort_order' => 1],
                            ['name' => 'Weights', 'description' => 'Weights and strength training', 'sort_order' => 2],
                            ['name' => 'Accessories', 'description' => 'Fitness accessories', 'sort_order' => 3],
                        ]
                    ],
                    [
                        'name' => 'Outdoor Sports',
                        'description' => 'Outdoor sports equipment',
                        'is_active' => true,
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Camping', 'description' => 'Camping and hiking gear', 'sort_order' => 1],
                            ['name' => 'Water Sports', 'description' => 'Swimming and water sports', 'sort_order' => 2],
                            ['name' => 'Team Sports', 'description' => 'Equipment for team sports', 'sort_order' => 3],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Books & Media',
                'description' => 'Books, magazines, and digital media',
                'is_active' => true,
                'sort_order' => 5,
                'children' => [
                    [
                        'name' => 'Books',
                        'description' => 'Physical and digital books',
                        'is_active' => true,
                        'sort_order' => 1,
                        'children' => [
                            ['name' => 'Fiction', 'description' => 'Fiction books and novels', 'sort_order' => 1],
                            ['name' => 'Non-Fiction', 'description' => 'Educational and reference books', 'sort_order' => 2],
                            ['name' => 'Technical', 'description' => 'Technical and professional books', 'sort_order' => 3],
                        ]
                    ],
                    [
                        'name' => 'Media',
                        'description' => 'Digital and physical media',
                        'is_active' => true,
                        'sort_order' => 2,
                        'children' => [
                            ['name' => 'Movies', 'description' => 'DVDs and digital movies', 'sort_order' => 1],
                            ['name' => 'Music', 'description' => 'CDs and digital music', 'sort_order' => 2],
                            ['name' => 'Games', 'description' => 'Video games and software', 'sort_order' => 3],
                        ]
                    ],
                ]
            ],
        ];

        $this->createCategoriesRecursive($categories);
    }

    /**
     * Create categories recursively with parent-child relationships.
     */
    private function createCategoriesRecursive(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'parent_id' => $parentId,
                'is_active' => $categoryData['is_active'] ?? true,
                'sort_order' => $categoryData['sort_order'] ?? 0,
            ]);

            if (!empty($children)) {
                $this->createCategoriesRecursive($children, $category->id);
            }
        }
    }
}
