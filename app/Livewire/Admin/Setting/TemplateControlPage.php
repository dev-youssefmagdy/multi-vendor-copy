<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Country;
use App\Models\Template;
use App\Repositories\TemplateRepository;
use App\Services\TemplateMediaService;
use App\Services\TemplateService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class TemplateControlPage extends Component
{
    use InteractsWithAdminUi;
    use AuthorizesAdminPermissions;
    use WithFileUploads;

    // Edit modal state
    public bool $showEditModal = false;
    public ?int $editingId = null;
    public string $editName = '';
    public string $editSlug = '';
    public string $editVersion = '';
    public string $editAuthor = '';
    public bool $removePreview = false;
    public ?string $existingPreviewUrl = null;

    public $previewImage = null;

    // Countries modal state
    public bool $showCountriesModal = false;
    public ?int $countriesTemplateId = null;
    public string $countriesTemplateName = '';
    public string $countriesSearch = '';
    public bool $countriesAll = true;
    /** @var array<int, int> */
    public array $selectedCountryIds = [];

    // ── Activate / Deactivate ────────────────────────────────────────────────

    public function requestActivate(int $templateId): void
    {
        $this->authorizePermission('settings.templates.manage');
        $template = Template::query()->findOrFail($templateId);

        $this->confirmAction('activate', [$templateId], [
            'title' => 'Activate template?',
            'text' => $template->name . ' will be marked as active.',
            'confirmButtonText' => 'Activate',
        ]);
    }

    public function activate(int $templateId, TemplateService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        $service->setActive(Template::query()->findOrFail($templateId));
        $this->toast('Template activated successfully.');
    }

    public function requestDeactivate(int $templateId): void
    {
        $this->authorizePermission('settings.templates.manage');
        $template = Template::query()->findOrFail($templateId);

        $this->confirmAction('deactivate', [$templateId], [
            'title' => 'Deactivate template?',
            'text' => $template->name . ' will be marked as inactive.',
            'confirmButtonText' => 'Deactivate',
        ]);
    }

    public function deactivate(int $templateId, TemplateService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        $service->setInactive(Template::query()->findOrFail($templateId));
        $this->toast('Template deactivated successfully.');
    }

    // ── Set Default ──────────────────────────────────────────────────────────

    public function requestSetDefault(int $templateId): void
    {
        $this->authorizePermission('settings.templates.manage');
        $template = Template::query()->withCount('countries')->findOrFail($templateId);

        // Guard on the server too — the button is hidden in the UI for restricted
        // templates, but we must still reject direct Livewire calls.
        if ($template->countries_count > 0) {
            $this->toast('Only "All countries" templates can be set as default.', 'error');
            return;
        }

        $this->confirmAction('setDefault', [$templateId], [
            'title' => 'Set as default template?',
            'text' => $template->name . ' will become the default storefront template and will be activated.',
            'confirmButtonText' => 'Set Default',
        ]);
    }

    public function setDefault(int $templateId, TemplateService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        try {
            $service->setDefault(Template::query()->findOrFail($templateId));
        } catch (\DomainException $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }
        $this->toast('Default template updated successfully.');
    }

    // ── Edit Modal ───────────────────────────────────────────────────────────

    public function openEdit(int $templateId): void
    {
        $this->authorizePermission('settings.templates.manage');
        $template = Template::query()->with('previewFile')->findOrFail($templateId);

        $this->editingId = $template->id;
        $this->editName = $template->name;
        $this->editSlug = $template->slug;
        $this->editVersion = $template->version ?? '';
        $this->editAuthor = $template->author ?? '';
        $this->removePreview = false;
        $this->previewImage = null;
        $this->existingPreviewUrl = $template->previewFile?->full_path;

        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->previewImage = null;
        $this->existingPreviewUrl = null;
        $this->resetErrorBag();
    }

    public function removePreviewImage(): void
    {
        $this->authorizePermission('settings.templates.manage');
        $this->removePreview = true;
        $this->previewImage = null;
    }

    public function saveEdit(TemplateService $service, TemplateMediaService $mediaService): void
    {
        $this->authorizePermission('settings.templates.manage');
        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editVersion' => ['nullable', 'string', 'max:50'],
            'editAuthor' => ['nullable', 'string', 'max:255'],
            'previewImage' => ['nullable', 'image', 'max:4096'],
        ]);

        $template = Template::query()->with('previewFile')->findOrFail($this->editingId);

        $service->update($template, [
            'name' => $validated['editName'],
            'version' => $validated['editVersion'] ?? null,
            'author' => $validated['editAuthor'] ?? null,
        ]);

        if ($this->removePreview) {
            $mediaService->purgePreview($template->fresh('previewFile'));
        }

        if ($this->previewImage) {
            $mediaService->syncPreview($template->fresh('previewFile'), $this->previewImage);
        }

        $this->closeEdit();
        $this->toast('Template updated successfully.');
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $repository = app(TemplateRepository::class);

        $countriesQuery = Country::query()->with('translations.language')->orderBy('name');
        if ($this->showCountriesModal && trim($this->countriesSearch) !== '') {
            $search = '%' . trim($this->countriesSearch) . '%';
            $countriesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('iso2', 'like', $search)
                    ->orWhere('iso3', 'like', $search);
            });
        }

        return view('livewire.admin.setting.template-control-page', [
            'templates' => $repository->all(),
            'stats' => $repository->stats(),
            'countries' => $this->showCountriesModal
                ? $countriesQuery->get(['id', 'iso2', 'name', 'flag_emoji'])
                : collect(),
        ]);
    }

    // ── Country Assignments ─────────────────────────────────────────────────

    public function openCountries(int $templateId): void
    {
        $this->authorizePermission('settings.templates.manage');
        $template = Template::query()->with('countries:id')->findOrFail($templateId);

        $this->countriesTemplateId = $template->id;
        $this->countriesTemplateName = $template->name;
        $this->selectedCountryIds = $template->countries->pluck('id')->map(fn($v) => (int) $v)->all();
        $this->countriesAll = empty($this->selectedCountryIds);
        $this->countriesSearch = '';
        $this->resetErrorBag();
        $this->showCountriesModal = true;
    }

    public function closeCountries(): void
    {
        $this->showCountriesModal = false;
        $this->countriesTemplateId = null;
        $this->selectedCountryIds = [];
        $this->countriesSearch = '';
    }

    public function toggleCountry(int $countryId): void
    {
        $this->authorizePermission('settings.templates.manage');
        $countryId = (int) $countryId;
        if (in_array($countryId, $this->selectedCountryIds, true)) {
            $this->selectedCountryIds = array_values(array_diff($this->selectedCountryIds, [$countryId]));
        } else {
            $this->selectedCountryIds[] = $countryId;
            $this->countriesAll = false;
        }
    }

    public function toggleAllCountries(): void
    {
        $this->authorizePermission('settings.templates.manage');
        $this->countriesAll = !$this->countriesAll;
        if ($this->countriesAll) {
            $this->selectedCountryIds = [];
        }
    }

    public function saveCountries(TemplateService $service): void
    {
        $this->authorizePermission('settings.templates.manage');
        if (!$this->countriesTemplateId) {
            return;
        }

        $template = Template::query()->findOrFail($this->countriesTemplateId);

        // "All countries" is represented by an empty pivot (saves space and keeps
        // the default inclusive semantics for new/unknown countries going forward).
        $ids = $this->countriesAll ? [] : array_values(array_unique(array_map('intval', $this->selectedCountryIds)));

        $service->syncCountries($template, $ids);

        $this->closeCountries();
        $this->toast('Template countries updated successfully.');
    }
}
