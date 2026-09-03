<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Repositories\AppSettingRepository;
use App\Services\AppSettingService;

class AiLogoLimitPage extends ContentPage
{
    public string $aiLogoGenerationLimit = '';

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys([
            'ai_logo_generation_limit',
        ]);

        $this->aiLogoGenerationLimit = $settings->get('ai_logo_generation_limit')?->value ?? '';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'AI Logo Generation Limits',
            'badge' => 'Policy',
            'description' => 'Limit how many times a vendor may generate a store logo with AI during onboarding and appearance setup.',
            'bullets' => [
                'Applies per store — each vendor gets their own generation count.',
                'Leave blank (or 0) to allow unlimited AI logo generations.',
                'Once a vendor reaches the limit, they can still upload a logo manually.',
            ],
        ];
    }

    public function save(AppSettingService $service): void
    {
        $validated = $this->validate([
            'aiLogoGenerationLimit' => ['nullable', 'integer', 'min:0'],
        ]);

        $service->saveMany([
            'ai_logo_generation_limit' => [
                'value' => $validated['aiLogoGenerationLimit'] ?? '',
                'type' => 'integer',
                'is_public' => false,
            ],
        ]);

        session()->flash('status', 'AI logo generation limit saved successfully.');
        session()->flash('status_type', 'success');
    }

    protected function pageData(): array
    {
        $stats = app(AppSettingRepository::class)->stats();

        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Limit',
            'cards' => [
                ['label' => 'Settings', 'value' => number_format($stats['total']), 'caption' => 'Central configuration records stored', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Public', 'value' => number_format($stats['public']), 'caption' => 'Publicly exposed configuration entries', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'fieldGroups' => [
                [
                    'title' => 'AI Logo Generation',
                    'description' => 'The maximum number of AI logo generations allowed per store. Leave blank or set to 0 to disable the limit.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        [
                            'label' => 'Generation Limit (ai_logo_generation_limit)',
                            'model' => 'aiLogoGenerationLimit',
                            'type' => 'text',
                            'placeholder' => 'e.g. 5',
                            'hint' => 'Number of times a vendor may use "Generate with AI" for their store logo. Blank/0 means unlimited.',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
