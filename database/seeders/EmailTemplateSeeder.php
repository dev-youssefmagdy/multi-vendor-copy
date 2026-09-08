<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Delegate to the central email template seeder which uses the catalog
        $this->call(CentralEmailTemplateSeeder::class);
    }
}
