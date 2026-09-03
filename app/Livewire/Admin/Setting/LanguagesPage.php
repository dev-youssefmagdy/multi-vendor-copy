<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\LanguageDirection;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\LanguageRepository;
use Livewire\Component;
use Livewire\WithPagination;

class LanguagesPage extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $directionFilter = '';
    public string $activeFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDirectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'directionFilter', 'activeFilter']);
        $this->resetPage();
    }

    public function deleteLanguage(int $languageId): void
    {
        $this->authorizePermission('settings.languages.manage');
        \App\Models\Language::query()->findOrFail($languageId)->delete();
        session()->flash('status', 'Language deleted successfully.');
    }

    public function render(LanguageRepository $languages)
    {
        return view('livewire.admin.setting.languages-page', [
            'languages' => $languages->paginate([
                'search' => $this->search,
                'direction' => $this->directionFilter,
                'is_active' => $this->activeFilter,
            ]),
            'stats' => $languages->stats(),
            'directionOptions' => LanguageDirection::cases(),
            'canManageLanguages' => $this->hasPermission('settings.languages.manage'),
        ]);
    }
}
