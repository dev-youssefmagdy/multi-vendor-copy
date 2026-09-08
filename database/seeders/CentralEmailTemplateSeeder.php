<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Support\EmailTemplateCatalog;
use Illuminate\Database\Seeder;

class CentralEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::withoutEvents(function (): void {
            foreach (EmailTemplateCatalog::definitions() as $template) {
                EmailTemplate::query()->updateOrCreate(
                    ['action' => $template['action']],
                    [
                        'name' => $template['name'],
                        'subject' => $template['subject'],
                        'body' => $template['body'],
                        'action' => $template['action'],
                        'type' => $template['type'],
                        'status' => $template['status'],
                    ]
                );
            }
        });

        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['email-templates']);
    }
}
