<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Models\Tenant\ProductRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Seeds demo data into the first available tenant after syncing central data.
 * Run CentralCatalogSeeder first to ensure products/categories exist centrally.
 */
class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();

        if (!$tenant) {
            $this->command?->warn('No tenant found. Skipping TenantDemoSeeder.');
            return;
        }

        // ── Sync central data into tenant ────────────────────────────────────
        $exitCode = Artisan::call('tenants:sync-data', [
            'tenant' => $tenant->id,
            '--ensure-database' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Tenant sync failed: ' . trim(Artisan::output()));
        }

        $this->command?->getOutput()->write(Artisan::output());

        // ── Seed tenant-specific demo data ───────────────────────────────────
        tenancy()->initialize($tenant);

        try {
            $this->seedBadges();
            $this->seedFlashSales();
            $this->seedProductRates();
        } finally {
            tenancy()->end();
        }

        $this->command?->info("Tenant {$tenant->id} demo data seeded.");
    }

    protected function seedBadges(): void
    {
        $newIn = ProductBadge::query()->firstOrCreate(
            ['text' => 'new-in'],
            ['active' => true]
        );

        $bestSelling = ProductBadge::query()->firstOrCreate(
            ['text' => 'best-selling'],
            ['active' => true]
        );

        // Assign "new-in" badge to the 10 most recently synced products
        $recentProducts = Product::query()
            ->where('active', true)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $newIn->products()->syncWithoutDetaching($recentProducts->pluck('id')->all());

        // Assign "best-selling" badge to 8 featured or older products
        $olderProducts = Product::query()
            ->where('active', true)
            ->whereDoesntHave('badges', fn($query) => $query->where('product_badges.id', $newIn->id))
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        $bestSelling->products()->syncWithoutDetaching($olderProducts->pluck('id')->all());
    }

    protected function seedFlashSales(): void
    {
        $products = Product::query()
            ->where('active', true)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        foreach ($products as $index => $product) {
            $flashSale = FlashSale::query()->firstOrCreate(
                ['product_id' => $product->id],
                [
                    'discount_percentage' => [10, 15, 20, 25, 30, 35][$index],
                    'start_date' => now()->subHour(),
                    'end_date' => now()->addDays(rand(1, 7)),
                    'active' => true,
                ]
            );

            // Also attach to the BelongsToMany pivot
            if (!$flashSale->products()->where('products.id', $product->id)->exists()) {
                $flashSale->products()->attach($product->id);
            }
        }
    }

    protected function seedProductRates(): void
    {
        $comments = [
            'Absolutely love this product! Great quality.',
            'Fast delivery and exactly as described.',
            'Very happy with this purchase. Will buy again!',
            'Good value for money.',
            'Exceeded my expectations. Highly recommended!',
            'Nice product, matches the description well.',
            'Great quality for the price.',
            'Ordered two of these. Both are perfect.',
            'Beautiful design, very elegant.',
            'Perfect gift idea. My friend loved it!',
            'Good product but packaging could be better.',
            'Excellent seller, item as described.',
        ];

        // Seed some demo customers if none exist
        $customers = [];
        $customerData = [
            ['full_name' => 'Sarah Johnson', 'email' => 'sarah.j@demo.com'],
            ['full_name' => 'Mike Chen', 'email' => 'mike.c@demo.com'],
            ['full_name' => 'Emily Davis', 'email' => 'emily.d@demo.com'],
            ['full_name' => 'James Wilson', 'email' => 'james.w@demo.com'],
            ['full_name' => 'Anna Martinez', 'email' => 'anna.m@demo.com'],
        ];

        foreach ($customerData as $data) {
            $customers[] = Customer::query()->firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => bcrypt('demo1234'), 'active' => true])
            );
        }

        $products = Product::query()->where('active', true)->limit(15)->get();

        foreach ($products as $product) {
            $rateCount = rand(2, 5);

            for ($i = 0; $i < $rateCount; $i++) {
                $customer = $customers[array_rand($customers)];

                // Avoid duplicate customer rates per product
                if (
                    ProductRate::query()
                        ->where('product_id', $product->id)
                        ->where('customer_id', $customer->id)
                        ->exists()
                ) {
                    continue;
                }

                ProductRate::query()->create([
                    'product_id' => $product->id,
                    'customer_id' => $customer->id,
                    'stars' => rand(3, 5),
                    'comment' => $comments[array_rand($comments)],
                ]);
            }
        }
    }
}
