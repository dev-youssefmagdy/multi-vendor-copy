<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CentralCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ── Categories ────────────────────────────────────────────────────────

//        $categoriesData = [
//            ['slug' => 'accessories', 'name' => 'Accessories', 'order' => 1, 'featured' => true],
//            ['slug' => 'women-fashion', 'name' => "Women's Fashion", 'order' => 2, 'featured' => true],
//            ['slug' => 'men-fashion', 'name' => "Men's Fashion", 'order' => 3, 'featured' => true],
//            ['slug' => 'jewelry', 'name' => 'Jewelry', 'order' => 4, 'featured' => true],
//            ['slug' => 'watches', 'name' => 'Watches', 'order' => 5, 'featured' => false],
//            ['slug' => 'bags-wallets', 'name' => 'Bags & Wallets', 'order' => 6, 'featured' => false],
//            ['slug' => 'home-decor', 'name' => 'Home Decor', 'order' => 7, 'featured' => false],
//            ['slug' => 'sports', 'name' => 'Sports', 'order' => 8, 'featured' => false],
//        ];
//
//        $categories = [];
//        foreach ($categoriesData as $data) {
//            $cat = Category::query()->firstOrCreate(
//                ['external_id' => 0],
//                [
//                    'status' => CategoryStatus::Published->value,
//                    'is_featured' => $data['featured'],
//                ]
//            );
//            $cat->catalogs()->syncWithoutDetaching([$catalog->id]);
//            $cat->syncTranslations([
//                'en' => [
//                    'name' => $data['name'],
//                    'slug' => $data['slug'],
//                    'description' => $data['name'] . ' category',
//                ]
//            ]);
//            $categories[$data['slug']] = $cat;
//        }
//
//        // ── Products ──────────────────────────────────────────────────────────
//
//        $products = [
//            // Accessories
//            [
//                'sku' => 'ACC-001',
//                'slug' => 'floral-headband',
//                'name' => 'Floral Embroidered Slim Twisted Headband',
//                'summary' => 'Elegant floral headband for daily wear',
//                'base_price' => 5.99,
//                'sale_price' => 2.34,
//                'categories' => ['accessories', 'women-fashion'],
//                'stock' => 200,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'ACC-002',
//                'slug' => 'woven-wide-headband',
//                'name' => 'Woven Textured Wide Headband',
//                'summary' => 'Stylish woven headband for all occasions',
//                'base_price' => 3.99,
//                'sale_price' => 1.47,
//                'categories' => ['accessories', 'women-fashion'],
//                'stock' => 150,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//
//            ],
//            [
//                'sku' => 'ACC-003',
//                'slug' => 'kids-hair-clips-10pack',
//                'name' => '10-Pack Kids Hair Clips Gold Glitter Unicorn',
//                'summary' => 'Adorable hair clips for kids parties and daily wear',
//                'base_price' => 8.00,
//                'sale_price' => 4.20,
//                'categories' => ['accessories'],
//                'stock' => 300,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Women Fashion
//            [
//                'sku' => 'WF-001',
//                'slug' => 'butterfly-pendant-necklace',
//                'name' => 'Blue Cubic Zirconia Butterfly Pendant Layered Necklace',
//                'summary' => 'Elegant layered necklace with butterfly pendant',
//                'base_price' => 4.50,
//                'sale_price' => 1.80,
//                'categories' => ['jewelry', 'women-fashion'],
//                'stock' => 180,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'WF-002',
//                'slug' => 'pearl-necklace-earrings-set',
//                'name' => 'New Pearl Necklace Earrings Two-Piece Set',
//                'summary' => 'Light luxury pearl necklace and earrings set',
//                'base_price' => 9.00,
//                'sale_price' => 4.40,
//                'categories' => ['jewelry', 'women-fashion'],
//                'stock' => 120,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'WF-003',
//                'slug' => 'minimalist-pendant-stacked-chain',
//                'name' => "Women's Minimalist Pendant Stacked Collarbone Chain",
//                'summary' => 'Minimalist gold-tone pendant necklace',
//                'base_price' => 10.00,
//                'sale_price' => 5.50,
//                'categories' => ['jewelry', 'women-fashion'],
//                'stock' => 90,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'WF-004',
//                'slug' => 'statement-disc-chain-belt',
//                'name' => "Women's Statement Disc Chain Belt",
//                'summary' => 'Trendy disc chain belt for women',
//                'base_price' => 6.50,
//                'sale_price' => 3.33,
//                'categories' => ['accessories', 'women-fashion'],
//                'stock' => 100,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'WF-005',
//                'slug' => 'sun-visor-embroidered',
//                'name' => 'Fantastic Embroidered Sun Visor Navy White',
//                'summary' => 'Adjustable sports sun visor for outdoor activities',
//                'base_price' => 8.00,
//                'sale_price' => 4.20,
//                'categories' => ['accessories', 'women-fashion'],
//                'stock' => 85,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Jewelry
//            [
//                'sku' => 'JW-001',
//                'slug' => 'snake-open-ring-gold',
//                'name' => "1PC Women's Gold-Tone Alloy Snake Open Ring",
//                'summary' => 'Fashion jewelry cubic zirconia snake ring',
//                'base_price' => 3.50,
//                'sale_price' => 1.50,
//                'categories' => ['jewelry', 'accessories'],
//                'stock' => 250,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'JW-002',
//                'slug' => 'minimalist-gold-necklace-cz',
//                'name' => 'Minimalist Gold-Tone Alloy Necklace Cubic Zirconia',
//                'summary' => 'Dainty fashion jewelry necklace for women',
//                'base_price' => 2.00,
//                'sale_price' => 0.78,
//                'categories' => ['jewelry'],
//                'stock' => 400,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Watches
//            [
//                'sku' => 'WT-001',
//                'slug' => 'yake-womens-pink-quartz-watch',
//                'name' => "Yake Women's Light Beige Alloy Quartz Watch Pink Dial",
//                'summary' => 'Elegant quartz watch with pink leather strap',
//                'base_price' => 12.00,
//                'sale_price' => 5.40,
//                'categories' => ['watches', 'accessories'],
//                'stock' => 60,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Home Decor
//            [
//                'sku' => 'HD-001',
//                'slug' => 'wooden-robot-car-ornament',
//                'name' => 'Wooden Robot Car Dashboard Ornament Natural Wood',
//                'summary' => 'Handmade wooden robot car interior decor',
//                'base_price' => 3.50,
//                'sale_price' => 1.50,
//                'categories' => ['home-decor'],
//                'stock' => 75,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'HD-002',
//                'slug' => 'solar-car-dashboard-ornament',
//                'name' => 'Wood Grain Solar-Powered Car Dashboard Ornament',
//                'summary' => 'Solar rotating mini car decor ornament',
//                'base_price' => 3.50,
//                'sale_price' => 1.50,
//                'categories' => ['home-decor'],
//                'stock' => 80,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Sports
//            [
//                'sku' => 'SP-001',
//                'slug' => 'air-vent-phone-mount',
//                'name' => '2-in-1 Air Vent CD Slot Car Phone Mount Universal',
//                'summary' => 'Adjustable hands-free car phone holder',
//                'base_price' => 4.50,
//                'sale_price' => 1.83,
//                'categories' => ['sports'],
//                'stock' => 200,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'SP-002',
//                'slug' => 'car-dashboard-cooling-fan',
//                'name' => '12V Car Dashboard Cooling Fan Adjustable Single Head',
//                'summary' => 'Car cooling fan with cigarette lighter plug',
//                'base_price' => 9.00,
//                'sale_price' => 4.09,
//                'categories' => ['sports'],
//                'stock' => 120,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Men Fashion
//            [
//                'sku' => 'MF-001',
//                'slug' => 'star-link-chain-belt',
//                'name' => "Women's Star Link Chain Belt",
//                'summary' => 'Stylish star-link chain belt accessory',
//                'base_price' => 6.00,
//                'sale_price' => 2.89,
//                'categories' => ['accessories', 'men-fashion'],
//                'stock' => 95,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            // Extra products for pagination
//            [
//                'sku' => 'ACC-004',
//                'slug' => 'argyle-bow-car-organizer',
//                'name' => 'Argyle Bow Car Seat Back Tissue Holder Fabric',
//                'summary' => 'Vehicle interior organizer for car seat',
//                'base_price' => 3.00,
//                'sale_price' => 1.35,
//                'categories' => ['home-decor'],
//                'stock' => 130,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'JW-003',
//                'slug' => 'miniature-house-model-kit',
//                'name' => 'Small House Handmade Assembly Miniature Model',
//                'summary' => 'Building block puzzle toy gift miniature house',
//                'base_price' => 15.00,
//                'sale_price' => 7.20,
//                'categories' => ['home-decor'],
//                'stock' => 50,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'WF-006',
//                'slug' => 'crystal-stud-earrings',
//                'name' => 'Crystal Stud Earrings Silver Plated',
//                'summary' => 'Sparkling crystal stud earrings for everyday wear',
//                'base_price' => 5.00,
//                'sale_price' => 2.50,
//                'categories' => ['jewelry', 'women-fashion'],
//                'stock' => 220,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'WF-007',
//                'slug' => 'velvet-scrunchie-set',
//                'name' => 'Velvet Scrunchie Set 6 Pack Assorted Colors',
//                'summary' => 'Soft velvet hair scrunchies for all hair types',
//                'base_price' => 4.00,
//                'sale_price' => 1.99,
//                'categories' => ['accessories', 'women-fashion'],
//                'stock' => 350,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//            [
//                'sku' => 'ACC-005',
//                'slug' => 'boho-anklet-bracelet',
//                'name' => 'Boho Layered Anklet Bracelet Gold Tone',
//                'summary' => 'Bohemian style layered anklet',
//                'base_price' => 4.50,
//                'sale_price' => 2.20,
//                'categories' => ['jewelry', 'accessories'],
//                'stock' => 180,
//                'weight' => rand(10, 100) / 100, // Random weight between 0.1 and 1.0 kg
//            ],
//        ];
//
//        foreach ($products as $data) {
//            $product = Product::query()->firstOrCreate(
//                ['sku' => $data['sku']],
//                [
//                    'slug' => $data['slug'],
//                    'status' => ProductStatus::Published->value,
//                    'delivery_scope' => DeliveryScope::AllZones->value,
//                    'base_price' => $data['base_price'],
//                    'sale_price' => $data['sale_price'],
//                    'stock' => $data['stock'],
//                    'manage_stock' => true,
//                    'is_taxable' => true,
//                    'requires_shipping' => true,
//                    'published_at' => now()->subDays(rand(1, 30)),
//                    'weight_grams' => $data['weight'],
//                ]
//            );
//
//            $product->syncTranslations([
//                'en' => [
//                    'name' => $data['name'],
//                    'summary' => $data['summary'],
//                    'description' => $data['summary'] . '. High quality product available in our store.',
//                ]
//            ]);
//
//            $catIds = array_filter(
//                array_map(fn($slug) => $categories[$slug]?->id ?? null, $data['categories'])
//            );
//            $product->categories()->syncWithoutDetaching($catIds);
//        }
    }
}
