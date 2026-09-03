<?php

namespace App\Livewire\Admin\Shipping;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Country;
use App\Models\DeliveryPopupDay;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

/**
 * Manage the delivery-time distribution shown in the storefront "Delivery"
 * popup modal on every product page.
 *
 * Records are grouped by country_id:
 *  - country_id = NULL  -> Global Default (fallback when customer country has no specific records)
 *  - country_id = X     -> Shown only when the customer's detected country is X
 */
class DeliveryPopupPage extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    // Modal state
    public bool  $showFormModal = false;
    public ?int  $recordId      = null;
    public ?int  $countryId     = null;
    public int   $dayNumber     = 1;
    public float $percentage    = 0.0;

    // Country picker search + pagination
    public string $countrySearch = '';
    public int    $countryPage   = 1;
    private const COUNTRY_PER_PAGE = 15;

    // Override view
    protected function pageView(): string
    {
        return 'livewire.admin.shipping.delivery-popup-page';
    }

    // Country picker computed property
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

    // Page meta
    protected function pageMeta(): array
    {
        return [
            'title'       => 'Delivery Popup Days',
            'badge'       => 'Shipping',
            'description' => 'Define per-country delivery probability (%) shown in the storefront "Delivery" popup. Records are grouped by country; the Global Default group is used when no country-specific data matches the customer.',
            'actionLabel' => 'Add Day',
            'tableTitle'  => 'Delivery Distribution',
            'headers'     => ['Day', 'Percentage', 'Bar Preview', 'Actions'],
        ];
    }

    // Page data
    protected function pageData(): array
    {
        $this->authorizeAnyPermission(['shipping.settings.view', 'shipping.settings.manage']);

        $allRecords = DeliveryPopupDay::query()
            ->with('country')
            ->orderByRaw('country_id IS NULL DESC')
            ->orderBy('country_id')
            ->orderBy('day_number')
            ->get();

        $totalRecords  = $allRecords->count();
        $countryGroups = $allRecords->whereNotNull('country_id')->groupBy('country_id')->count();
        $hasDefault    = $allRecords->whereNull('country_id')->isNotEmpty();

        $groups = $allRecords
            ->groupBy(fn($r) => $r->country_id ?? '__default__')
            ->map(function ($records, $key) {
                $first   = $records->first();
                $country = $key === '__default__' ? null : $first->country;
                $sumPct  = $records->sum(fn($r) => (float) $r->percentage);
                $pctOk   = $sumPct >= 99 && $sumPct <= 101;

                $rows = $records->map(function (DeliveryPopupDay $row) {
                    $pct = (float) $row->percentage;
                    $bar = '<div style="display:flex;align-items:center;gap:8px;">'
                        . '<div style="background:var(--elevated,#D4D4D4);border-radius:26px;height:8px;width:120px;overflow:hidden;">'
                        . '<div style="background:var(--t1,#121212);height:8px;border-radius:26px;width:' . min((int) ($pct * 1.2), 120) . 'px;"></div>'
                        . '</div>'
                        . '<span style="font-size:11px;color:var(--t3,#666);">' . number_format($pct, 1) . '%</span>'
                        . '</div>';

                    return [
                        '<div class="entity-title">' . $row->day_number . ' ' . ($row->day_number === 1 ? 'day' : 'days') . '</div>',
                        number_format($pct, 2) . '%',
                        $bar,
                        '<div class="flex gap-2">'
                        . '<button type="button" class="btn btn-secondary btn-sm" wire:click="openEditModal(' . $row->id . ')">Edit</button>'
                        . '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $row->id . ')">Delete</button>'
                        . '</div>',
                    ];
                })->all();

                return [
                    'country_id' => $key === '__default__' ? null : (int) $key,
                    'label'      => $country
                        ? ($country->flag_emoji ?? '') . ' ' . $country->name . ' (' . $country->iso2 . ')'
                        : 'Global Default',
                    'is_default' => $key === '__default__',
                    'sum_pct'    => round($sumPct, 2),
                    'pct_ok'     => $pctOk,
                    'rows'       => $rows,
                ];
            })
            ->values()
            ->all();

        return array_merge(parent::pageData(), [
            'actionMethod'      => 'openCreateModal',
            'groups'            => $groups,
            'statistics'        => [
                ['label' => 'Day Entries',    'value' => number_format($totalRecords),  'caption' => 'Total day-probability rows across all groups.',              'dot' => 'dot-cyan',  'glow' => 'card-glow-cyan'],
                ['label' => 'Country Groups', 'value' => number_format($countryGroups), 'caption' => 'Countries with specific popup day distributions.',           'dot' => 'dot-amber'],
                ['label' => 'Global Default', 'value' => $hasDefault ? 'Set' : 'Missing', 'caption' => 'Fallback shown when customer country has no records.',   'dot' => $hasDefault ? 'dot-green' : 'dot-red', 'glow' => $hasDefault ? 'card-glow-green' : 'card-glow-amber'],
            ],
            'modalModel'        => 'showFormModal',
            'modalTitle'        => $this->recordId ? 'Edit Day Entry' : 'Add Day Entry',
            'modalCloseAction'  => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel'  => $this->recordId ? 'Update' : 'Create',
            'modalMaxWidth'     => 'lg',
            'modalFieldGroups'  => [],
            'modalContentView'  => 'livewire.admin.shipping.delivery-popup-form',
        ]);
    }

    // Modal actions
    public function openCreateModal(?int $countryId = null): void
    {
        $this->reset(['recordId', 'countryId', 'dayNumber', 'percentage', 'countrySearch', 'countryPage']);
        $this->countryId     = $countryId;
        $this->dayNumber     = 1;
        $this->percentage    = 0.0;
        $this->countryPage   = 1;
        $this->showFormModal = true;
        $this->resetErrorBag();
    }

    public function openEditModal(int $id): void
    {
        $row = DeliveryPopupDay::findOrFail($id);
        $this->recordId      = $row->id;
        $this->countryId     = $row->country_id;
        $this->dayNumber     = $row->day_number;
        $this->percentage    = (float) $row->percentage;
        $this->countrySearch = '';
        $this->countryPage   = 1;
        $this->showFormModal = true;
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->reset(['recordId', 'countryId', 'dayNumber', 'percentage', 'countrySearch', 'countryPage']);
        $this->countryPage = 1;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorizePermission('shipping.settings.manage');

        $this->validate([
            'countryId'  => ['nullable', 'integer', 'exists:countries,id'],
            'dayNumber'  => ['required', 'integer', 'min:1', 'max:365'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        // Duplicate check within the same country group
        $duplicate = DeliveryPopupDay::query()
            ->when(
                $this->countryId !== null,
                fn($q) => $q->where('country_id', $this->countryId),
                fn($q) => $q->whereNull('country_id')
            )
            ->where('day_number', $this->dayNumber)
            ->when($this->recordId, fn($q) => $q->where('id', '!=', $this->recordId))
            ->exists();

        if ($duplicate) {
            $this->addError('dayNumber', 'Day ' . $this->dayNumber . ' already exists in this country group.');
            return;
        }

        DeliveryPopupDay::updateOrCreate(
            ['id' => $this->recordId ?: 0],
            [
                'country_id' => $this->countryId,
                'day_number' => $this->dayNumber,
                'percentage' => $this->percentage,
            ]
        );

        $this->toast($this->recordId ? 'Day entry updated.' : 'Day entry created.');
        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmAction('deleteRecord', [$id], [
            'title'             => 'Delete day entry?',
            'confirmButtonText' => 'Delete',
        ]);
    }

    public function deleteRecord(int $id): void
    {
        $this->authorizePermission('shipping.settings.manage');
        DeliveryPopupDay::findOrFail($id)->delete();
        $this->toast('Day entry deleted.');
    }
}
