<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Oak Dining Chair',
                'sku' => 'DP-CH-001',
                'price' => 450000,
                'stock' => 80,
                'description' => 'Solid oak dining chair with premium finish.',
            ],
            [
                'name' => 'Minimalist Coffee Table',
                'sku' => 'DP-TB-002',
                'price' => 950000,
                'stock' => 40,
                'description' => 'Modern coffee table with storage shelf.',
            ],
            [
                'name' => 'Scandinavian Sofa 3-Seater',
                'sku' => 'DP-SF-003',
                'price' => 3250000,
                'stock' => 18,
                'description' => 'Comfortable sofa with soft linen upholstery.',
            ],
            [
                'name' => 'Wooden Bed Frame Queen',
                'sku' => 'DP-BD-004',
                'price' => 2850000,
                'stock' => 12,
                'description' => 'Sturdy queen bed frame made of teak wood.',
            ],
            [
                'name' => 'Office Work Desk',
                'sku' => 'DP-DS-005',
                'price' => 1250000,
                'stock' => 25,
                'description' => 'Ergonomic desk with cable management.',
            ],
            [
                'name' => 'Bookshelf 5-Tier',
                'sku' => 'DP-SH-006',
                'price' => 850000,
                'stock' => 30,
                'description' => 'Tall bookshelf with 5 spacious tiers.',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
