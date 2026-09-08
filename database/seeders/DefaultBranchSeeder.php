<?php

namespace Database\Seeders;

use App\Enums\ShippingZoneStatus;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Country;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRate;
use Illuminate\Database\Seeder;

class DefaultBranchSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure only one default branch exists
        $defaultBranch = Branch::query()->firstOrCreate(
            ['code' => 'main'],
            [
                'name' => 'Main Branch',
                'phone' => null,
                'email' => null,
                'address' => null,
                'city' => null,
                'country' => null,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        if (!$defaultBranch->is_default) {
            $defaultBranch->update(['is_default' => true]);
        }

        // Create a default shipping zone for the main branch if none exist
        if ($defaultBranch->shippingZones()->count() === 0) {
            $egypt = Country::query()->where('iso2', 'EG')->first();

            $zone = ShippingZone::query()->create([
                'branch_id' => $defaultBranch->id,
                'country_id' => $egypt?->id,
                'name' => 'Local Delivery',
                'code' => 'local',
                'currency_code' => 'USD',
                'status' => ShippingZoneStatus::Active->value,
            ]);

            ShippingZoneRate::query()->firstOrCreate(
                ['shipping_zone_id' => $zone->id, 'name' => 'Standard'],
                ['min_weight' => 0, 'max_weight' => 1000, 'price' => 10, 'is_active' => true]
            );
        }

        // Assign all products to the default branch
        $products = Product::query()->get();

        foreach ($products as $product) {
            BranchProduct::query()->firstOrCreate(
                [
                    'branch_id' => $defaultBranch->id,
                    'product_id' => $product->id,
                    'variation_id' => null,
                ],
                ['quantity' => $product->stock ?? 0]
            );
        }

        $this->command->info("Default branch seeded with {$products->count()} products.");
    }
}
