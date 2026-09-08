<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\LanguageDirection;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Language;
use App\Repositories\LanguageRepository;
use Livewire\Component;
use Livewire\WithPagination;

class LanguagesPage extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;
    use WithPagination;

    protected int $perPage = 10;

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

    public function retryTranslation(int $languageId): void
    {
        $this->authorizePermission('settings.languages.manage');

        $language = Language::query()->findOrFail($languageId);

        \App\Jobs\TranslateLanguageCatalogJob::dispatch($language->id, $language->translation_source_locale)->afterCommit();

        $this->toast('Translation restarted.');
    }

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('settings.languages.manage');

        $offset = ($this->getPage() - 1) * $this->perPage;

        foreach ($orderedIds as $index => $languageId) {
            Language::query()->where('id', (int) $languageId)->update(['sort_order' => $offset + $index]);
        }

        $this->toast('Language order saved.');
    }

    public function render(LanguageRepository $languages)
    {
        return view('livewire.admin.setting.languages-page', [
            'languages' => $languages->paginate([
                'search' => $this->search,
                'direction' => $this->directionFilter,
                'is_active' => $this->activeFilter,
            ], $this->perPage),
            'stats' => $languages->stats(),
            'directionOptions' => LanguageDirection::cases(),
            'canManageLanguages' => $this->hasPermission('settings.languages.manage'),
        ]);
    }
}
