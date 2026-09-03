<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateTranslation;
use App\Models\Language;
use App\Services\OpenAiTranslationService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Database\Seeder;

class EmailTemplateTranslationSeeder extends Seeder
{
    public function __construct(
        private readonly OpenAiTranslationService $openAi,
        private readonly CentralCatalogTenantSyncService $syncService,
    ) {
    }

    public function run(): void
    {
        if (!$this->openAi->configured()) {
            $this->command->warn('OpenAI API key is not configured — skipping email template translation seeder.');
            return;
        }

        $sourceLocale = Language::query()->where('is_default', true)->value('code')
            ?? config('app.fallback_locale', 'en');

        $languages = Language::query()
            ->where('is_active', true)
            ->where('code', '!=', $sourceLocale)
            ->orderBy('name')
            ->get();

        if ($languages->isEmpty()) {
            $this->command->info('No additional active languages found — nothing to translate.');
            return;
        }

        $templates = EmailTemplate::query()
            ->with('translations')
            ->orderBy('id')
            ->get();

        if ($templates->isEmpty()) {
            $this->command->info('No email templates found — nothing to translate.');
            return;
        }

        $this->command->info(sprintf(
            'Translating %d email templates into %d language(s)…',
            $templates->count(),
            $languages->count(),
        ));


        foreach ($languages as $language) {
            $targetLocale = strtolower((string) $language->code);
            $targetLanguage = $language->name;

            $this->command->info("  → {$targetLanguage} ({$targetLocale})");

            // Build flat list of items to translate: [template_id, field, text]
            $pending = [];

            foreach ($templates as $template) {
                $existingSubject = trim(
                    (string) ($template->translations->firstWhere('locale', $targetLocale)?->subject ?? '')
                );
                $existingBody = trim(
                    (string) ($template->translations->firstWhere('locale', $targetLocale)?->body ?? '')
                );

                // Skip subject if already translated
                if ($existingSubject === '' || $existingSubject === $template->subject) {
                    $pending[] = [
                        'template_id' => $template->id,
                        'field' => 'subject',
                        'text' => $template->subject,
                    ];
                }

                // Skip body if already translated
                $defaultBody = trim((string) ($template->body ?? ''));
                if ($defaultBody !== '' && ($existingBody === '' || $existingBody === $defaultBody)) {
                    $pending[] = [
                        'template_id' => $template->id,
                        'field' => 'body',
                        'text' => $defaultBody,
                    ];
                }
            }

            if (empty($pending)) {
                $this->command->line("     Already fully translated — skipping.");
                continue;
            }

            $translated = $this->openAi->translateBatch(
                array_map(fn(array $item) => $item['text'], $pending),
                $sourceLocale,
                $targetLocale,
                $targetLanguage,
                'Transactional email template for an ecommerce storefront. Preserve {{placeholder}} tokens, HTML tags, and inline styles exactly.',
            );

            // Group translated values by template_id → [subject, body]
            $byTemplate = [];

            foreach ($pending as $offset => $item) {
                $byTemplate[$item['template_id']][$item['field']] = trim((string) ($translated[$offset] ?? $item['text']));
            }

            foreach ($byTemplate as $templateId => $fields) {
                $template = $templates->firstWhere('id', $templateId);
                $subject = $fields['subject'] ?? $template->subject;
                $body = $fields['body'] ?? null;

                EmailTemplateTranslation::query()->updateOrCreate(
                    ['email_template_id' => $templateId, 'locale' => $targetLocale],
                    ['subject' => $subject, 'body' => $body],
                );
            }

            $this->command->line('     Done.');
        }


        // set $sourceLocale translations for all templates
        foreach ($templates as $template) {
            EmailTemplateTranslation::query()->updateOrCreate(
                ['email_template_id' => $template->id, 'locale' => $sourceLocale],
                ['subject' => $template->subject, 'body' => $template->body],
            );
        }

        $this->command->info('Syncing translations to all tenants…');
        $this->syncService->syncAllTenants(['email-templates']);
        $this->command->info('Email template translations seeded successfully.');
    }
}
