<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WorldCurrenciesSeeder::class,
            WorldCountriesSeeder::class,
            WorldCitiesSeeder::class,
            CentralAdminSeeder::class,
            AdminPermissionSeeder::class,
            CentralCatalogSeeder::class,
                // TenantDemoSeeder::class,
            CentralPaymentGatewaySeeder::class,
            EmailTemplateSeeder::class,
            EmailTemplateTranslationSeeder::class,
            AddPaymentGatewayLimitPermissionSeeder::class,
            AddReturnPolicyPermissionSeeder::class,
        ]);

        Artisan::call('currencies:update-rates');
        // php artisan neozena:import --sync
        // Artisan::call('neozena:import --sync');
    }
}
