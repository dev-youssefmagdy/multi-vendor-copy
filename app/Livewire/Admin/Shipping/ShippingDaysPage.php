<?php

namespace App\Livewire\Admin\Shipping;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Country;
use App\Models\ShippingDayRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

/**
 * Central admin CRUD for per-country default shipping days.
 *
 * A rule with country_code = null acts as the global fallback shown on the
 * storefront when no country-specific rule matches the customer's country.
 */
class ShippingDaysPage extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    // ── Modal state ───────────────────────────────────────────────────────────
    public bool $showFormModal = false;
    public ?int $recordId = null;
    public string $countryCode = '';   // '' = global default
    public int $minDays = 3;
    public int $maxDays = 7;

    // ── Country picker search + pagination ───────────────────────────────────
    public string $countrySearch = '';
    public int $countryPage = 1;
    private const COUNTRY_PER_PAGE = 15;

    // ── Country picker helpers ──────────────────────────────────────────────

    public function getCountryResultsProperty(): LengthAwarePaginator
    {
        return Country::query()
            ->when($this->countrySearch !== '', function ($q) {
                $search = '%' . $this->countrySearch . '%';
                $q->where('name', 'like', $search)->orWhere('iso2', 'like', $search);
            })
            ->orderBy('name')
            ->paginate(self::COUNTRY_PER_PAGE, ['*'], 'countryPage', $this->countryPage);
    }

    public function updatedCountrySearch(): void
    {
        $this->countryPage = 1;
    }

    public function countryPageNext(): void
    {
        if ($this->countryPage < $this->countryResults->lastPage()) {
            $this->countryPage++;
        }
    }

    public function countryPagePrev(): void
    {
        if ($this->countryPage > 1) {
            $this->countryPage--;
        }
    }

    // ── Page meta ─────────────────────────────────────────────────────────────

    protected function pageMeta(): array
    {
        return [
            'title' => 'Default Shipping Days',
            'badge' => 'Shipping',
            'description' => 'Configure the minimum and maximum default delivery days per country. The "Global Default" row is used when the customer country has no specific rule.',
            'actionLabel' => 'Add Rule',
            'tableTitle' => 'Shipping Day Rules',
            'headers' => ['Country', 'Min Days', 'Max Days', 'Actions'],
        ];
    }

    // ── Page data ─────────────────────────────────────────────────────────────

    protected function pageData(): array
    {
        $this->authorizeAnyPermission(['shipping.settings.view', 'shipping.settings.manage']);

        $records = ShippingDayRule::query()
            ->orderByRaw('country_code IS NOT NULL, country_code ASC')
            ->paginate(30);

        $countryNames = Country::query()->with('translations.language')->orderBy('name')->get(['id', 'name', 'iso2'])->pluck('name', 'iso2');

        $rows = collect($records->items())->map(function (ShippingDayRule $rule) use ($countryNames) {
            $label = $rule->country_code
                ? ($countryNames[$rule->country_code] ?? $rule->country_code) . ' (' . strtoupper($rule->country_code) . ')'
                : '<span class="badge badge-cyan">Global Default</span>';

            return [
                $label,
                $rule->min_days . ' days',
                $rule->max_days . ' days',
                '<div class="flex gap-2">'
                . '<button type="button" class="btn btn-secondary btn-sm" wire:click="openEditModal(' . $rule->id . ')">Edit</button>'
                . '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $rule->id . ')">Delete</button>'
                . '</div>',
            ];
        })->all();

        $total = ShippingDayRule::query()->count();
        $hasGlobal = ShippingDayRule::query()->whereNull('country_code')->exists();
        $countryRules = ShippingDayRule::query()->whereNotNull('country_code')->count();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $records,
            'rows' => $rows,
            'statistics' => [
                ['label' => 'Total Rules', 'value' => number_format($total), 'caption' => 'Configured shipping day rules.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Country Rules', 'value' => number_format($countryRules), 'caption' => 'Country-specific overrides.', 'dot' => 'dot-amber'],
                ['label' => 'Global Default', 'value' => $hasGlobal ? '✓ Set' : '✗ Missing', 'caption' => 'Fallback when country not matched.', 'dot' => $hasGlobal ? 'dot-green' : 'dot-red', 'glow' => $hasGlobal ? 'card-glow-green' : 'card-glow-amber'],
            ],

            // ── Modal ──────────────────────────────────────────────────────
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->recordId ? 'Edit Shipping Rule' : 'Add Shipping Rule',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->recordId ? 'Update' : 'Create',
            'modalMaxWidth' => 'lg',
            'modalFieldGroups' => [],
            'modalContentView' => 'livewire.admin.shipping.shipping-days-form',
        ]);
    }

    // ── Modal actions ─────────────────────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->reset(['recordId', 'countryCode', 'minDays', 'maxDays', 'countrySearch', 'countryPage']);
        $this->minDays = 3;
        $this->maxDays = 7;
        $this->countryPage = 1;
        $this->showFormModal = true;
        $this->resetErrorBag();
    }

    public function openEditModal(int $id): void
    {
        $rule = ShippingDayRule::findOrFail($id);
        $this->recordId = $rule->id;
        $this->countryCode = $rule->country_code ?? '';
        $this->minDays = $rule->min_days;
        $this->maxDays = $rule->max_days;
        $this->countrySearch = '';
        $this->countryPage = 1;
        $this->showFormModal = true;
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->reset(['recordId', 'countryCode', 'minDays', 'maxDays', 'countrySearch', 'countryPage']);
        $this->countryPage = 1;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorizePermission('shipping.settings.manage');

        $this->validate([
            'countryCode' => ['nullable', 'string', 'max:2'],
            'minDays' => ['required', 'integer', 'min:1', 'max:365'],
            'maxDays' => ['required', 'integer', 'min:1', 'max:365', 'gte:minDays'],
        ]);

        $code = $this->countryCode !== '' ? strtoupper($this->countryCode) : null;

        // When creating a new rule ensure uniqueness on country_code
        if (!$this->recordId) {
            $conflict = ShippingDayRule::query()
                ->when($code, fn($q) => $q->where('country_code', $code), fn($q) => $q->whereNull('country_code'))
                ->exists();

            if ($conflict) {
                $this->addError('countryCode', $code ? "A rule for country $code already exists." : 'The global default rule already exists.');
                return;
            }
        }

        ShippingDayRule::updateOrCreate(
            ['id' => $this->recordId ?: 0],
            ['country_code' => $code, 'min_days' => $this->minDays, 'max_days' => $this->maxDays]
        );

        $this->toast($this->recordId ? 'Shipping rule updated.' : 'Shipping rule created.');
        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmAction('deleteRecord', [$id], [
            'title' => 'Delete shipping rule?',
            'confirmButtonText' => 'Delete',
        ]);
    }

    public function deleteRecord(int $id): void
    {
        $this->authorizePermission('shipping.settings.manage');
        ShippingDayRule::findOrFail($id)->delete();
        $this->toast('Shipping rule deleted.');
    }
}
