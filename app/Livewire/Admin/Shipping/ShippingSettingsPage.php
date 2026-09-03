<?php

namespace App\Livewire\Admin\Shipping;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AppSetting;
use App\Models\ShippingStop;
use App\Repositories\AppSettingRepository;
use App\Services\AppSettingService;
use Livewire\WithPagination;

class ShippingSettingsPage extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    // ── Default shipping days ────────────────────────────────────────────────
    public string $defaultShippingDays = '3';

    // ── Stop period form ─────────────────────────────────────────────────────
    public bool $showFormModal = false;
    public ?int $stopId = null;
    public string $fromDate = '';
    public string $toDate = '';
    public string $reason = '';
    public bool $stopActive = true;

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys(['default_shipping_days']);
        $this->defaultShippingDays = $settings->get('default_shipping_days')?->value ?? '3';
    }

    protected function pageView(): string
    {
        return 'livewire.admin.shipping.shipping-settings-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Shipping Settings',
            'badge' => 'Shipping Management',
            'description' => 'Configure the default shipping duration and set stop periods (e.g. holidays) that delay delivery for all tenants.',
            'actionLabel' => 'Add Stop Period',
            'tableTitle' => 'Shipping Stop Periods',
            'headers' => ['From', 'To', 'Days', 'Reason', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $stops = ShippingStop::query()->orderBy('from_date')->paginate(15);

        $rows = collect($stops->items())->map(fn(ShippingStop $stop) => [
            e($stop->from_date->format('M d, Y')),
            e($stop->to_date->format('M d, Y')),
            e((string) $stop->daysCount()),
            e($stop->reason ?: '-'),
            '<span class="badge ' . ($stop->active ? 'badge-green' : 'badge-amber') . '">' . e($stop->active ? 'Active' : 'Inactive') . '</span>',
            '<div class="flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="editStop(' . $stop->id . ')">Edit</button>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDeleteStop(' . $stop->id . ')">Delete</button>
            </div>',
        ])->all();

        $totalStops = ShippingStop::query()->count();
        $activeStops = ShippingStop::query()->where('active', true)->count();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $stops,
            'rows' => $rows,
            'statistics' => [
                ['label' => 'Default Days', 'value' => $this->defaultShippingDays, 'caption' => 'Standard shipping days to arrive', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Stop Periods', 'value' => number_format($totalStops), 'caption' => 'Configured delay windows', 'dot' => 'dot-amber'],
                ['label' => 'Active Stops', 'value' => number_format($activeStops), 'caption' => 'Currently applying extra delays', 'dot' => 'dot-red', 'glow' => 'card-glow-amber'],
            ],
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->stopId ? 'Edit Stop Period' : 'Add Stop Period',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'saveStop',
            'modalSubmitLabel' => $this->stopId ? 'Update Stop Period' : 'Create Stop Period',
            'modalFieldGroups' => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'From Date', 'model' => 'fromDate', 'type' => 'date'],
                        ['label' => 'To Date', 'model' => 'toDate', 'type' => 'date'],
                        ['label' => 'Reason', 'model' => 'reason', 'type' => 'text', 'wrapperClass' => 'span-2', 'placeholder' => 'e.g. National holiday, warehouse maintenance'],
                        ['label' => 'Active', 'model' => 'stopActive', 'type' => 'checkbox', 'toggleLabel' => 'This stop period is active'],
                    ],
                ],
            ],
        ]);
    }

    // ── Default shipping days ────────────────────────────────────────────────

    public function saveDefaultDays(AppSettingService $service): void
    {
        $this->authorizePermission('shipping.settings.manage');

        $validated = $this->validate([
            'defaultShippingDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $service->saveMany([
            'default_shipping_days' => ['value' => (string) $validated['defaultShippingDays'], 'is_public' => true],
        ]);

        $this->syncStopsToAppSettings();
        $this->toast(__('Default shipping days saved and synced to all tenants.'));
    }

    // ── Stop period CRUD ─────────────────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->stopId = null;
        $this->fromDate = '';
        $this->toDate = '';
        $this->reason = '';
        $this->stopActive = true;
        $this->showFormModal = true;
    }

    public function editStop(int $stopId): void
    {
        $stop = ShippingStop::query()->findOrFail($stopId);
        $this->stopId = $stop->id;
        $this->fromDate = $stop->from_date->format('Y-m-d');
        $this->toDate = $stop->to_date->format('Y-m-d');
        $this->reason = $stop->reason ?? '';
        $this->stopActive = $stop->active;
        $this->showFormModal = true;
    }

    public function saveStop(AppSettingService $service): void
    {
        $this->authorizePermission('shipping.settings.manage');

        $validated = $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
            'reason' => ['nullable', 'string', 'max:255'],
            'stopActive' => ['boolean'],
        ]);

        if ($this->stopId) {
            ShippingStop::query()->findOrFail($this->stopId)->update([
                'from_date' => $validated['fromDate'],
                'to_date' => $validated['toDate'],
                'reason' => $validated['reason'] ?? null,
                'active' => $validated['stopActive'],
            ]);
        } else {
            ShippingStop::query()->create([
                'from_date' => $validated['fromDate'],
                'to_date' => $validated['toDate'],
                'reason' => $validated['reason'] ?? null,
                'active' => $validated['stopActive'],
            ]);
        }

        $this->syncStopsToAppSettings($service);
        $this->closeModal();
        $this->toast($this->stopId ? __('Stop period updated and synced.') : __('Stop period created and synced.'));
    }

    public function confirmDeleteStop(int $stopId): void
    {
        $this->confirmAction('deleteStop', [$stopId], [
            'title' => 'Delete stop period?',
            'confirmButtonText' => 'Delete stop period',
        ]);
    }

    public function deleteStop(int $stopId, AppSettingService $service): void
    {
        $this->authorizePermission('shipping.settings.manage');

        ShippingStop::query()->findOrFail($stopId)->delete();
        $this->syncStopsToAppSettings($service);
        $this->toast(__('Stop period deleted and synced.'));
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
    }

    // ── Sync stops to app_settings (so tenants can read them) ───────────────

    protected function syncStopsToAppSettings(?AppSettingService $service = null): void
    {
        $service ??= app(AppSettingService::class);

        $stops = ShippingStop::query()
            ->where('active', true)
            ->orderBy('from_date')
            ->get()
            ->map(fn(ShippingStop $stop) => [
                'from' => $stop->from_date->toDateString(),
                'to' => $stop->to_date->toDateString(),
                'reason' => $stop->reason,
            ])
            ->all();

        $service->saveMany([
            'shipping_stop_periods' => [
                'value' => json_encode($stops, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_public' => false,
            ],
        ]);
    }
}
