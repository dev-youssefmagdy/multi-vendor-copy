<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Repositories\MaintenanceRepository;
use App\Services\MaintenanceService;

class MaintenanceModePage extends ContentPage
{
    public string $message = '';
    public bool $isActive = false;
    public ?string $startsAt = null;
    public ?string $endsAt = null;

    public function mount(): void
    {
        $record = app(MaintenanceRepository::class)->current();
        $this->message = $record?->message ?? '';
        $this->isActive = $record?->is_active ?? false;
        $this->startsAt = $record?->starts_at?->format('Y-m-d\TH:i');
        $this->endsAt = $record?->ends_at?->format('Y-m-d\TH:i');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Maintenance Mode',
            'badge' => 'Operations',
            'description' => 'Control central maintenance messaging, storefront restrictions, and operator bypass rules.',
            'bullets' => [
                'Store maintenance mode state in the settings table.',
                'Allow custom translated maintenance messages.',
                'Separate central maintenance from tenant-facing maintenance later.',
            ],
        ];
    }

    public function save(MaintenanceService $service): void
    {
        $validated = $this->validate([
            'message' => ['nullable', 'string'],
            'isActive' => ['boolean'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date'],
        ]);

        $service->save([
            'message' => $validated['message'] ?? null,
            'is_active' => $validated['isActive'],
            'starts_at' => $validated['startsAt'] ?? null,
            'ends_at' => $validated['endsAt'] ?? null,
        ]);

        session()->flash('status', 'Maintenance mode settings saved successfully.');
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Maintenance',
            'fieldGroups' => [
                [
                    'title' => 'Maintenance Window',
                    'description' => 'Control the message and active maintenance window.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Message', 'model' => 'message', 'type' => 'textarea', 'rows' => 5, 'wrapperClass' => 'span-2'],
                        ['label' => 'Starts At', 'model' => 'startsAt', 'type' => 'datetime-local'],
                        ['label' => 'Ends At', 'model' => 'endsAt', 'type' => 'datetime-local'],
                        ['label' => 'Active', 'model' => 'isActive', 'type' => 'checkbox', 'toggleLabel' => 'Enable maintenance mode'],
                    ],
                ],
            ],
        ]);
    }
}
