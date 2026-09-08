<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Services\TranslationFileService;
use InvalidArgumentException;
use Livewire\Component;

class TranslationsPage extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;

    // Pagination state
    public int $page = 1;
    public int $perPage = 25;

    public string $selectedResource = 'json';
    public string $search = '';
    public bool $showOnlyMissing = false;
    public string $newPhpFile = '';

    public array $resources = [];
    public array $locales = [];
    public array $rows = [];

    protected $queryString = ['page'];

    public function mount(TranslationFileService $service): void
    {
        $this->refreshResources($service);

        if (!$this->resourceExists($this->selectedResource)) {
            $this->selectedResource = $this->resources[0]['key'] ?? 'json';
        }

        $this->loadResource($service);
    }

    public function updatedSelectedResource(TranslationFileService $service): void
    {
        $this->page = 1;
        $this->loadResource($service);
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedShowOnlyMissing(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, (int) $page);
    }

    public function addRow(): void
    {
        $this->authorizePermission('settings.languages.manage');

        $values = [];
        foreach ($this->locales as $locale) {
            $values[$locale] = '';
        }

        $this->rows[] = [
            'key' => '',
            'values' => $values,
        ];
    }

    public function removeRow(int $index, TranslationFileService $service): void
    {
        $this->authorizePermission('settings.languages.manage');

        if (!isset($this->rows[$index])) {
            $this->toast('Translation row could not be found.', 'error');
            return;
        }

        $removedRow = $this->rows[$index];
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if (!$this->persistRows($service)) {
            array_splice($this->rows, $index, 0, [$removedRow]);
            $this->toast('Translation row could not be removed.', 'error');
            return;
        }

        $this->toast('Translation row removed successfully.');
    }

    public function saveRow(int $index, TranslationFileService $service): void
    {
        $this->authorizePermission('settings.languages.manage');

        if (!isset($this->rows[$index])) {
            $this->toast('Translation row could not be found.', 'error');
            return;
        }

        if (!$this->persistRows($service, $index)) {
            return;
        }

        $this->toast('Translation row saved successfully.');
    }

    public function createPhpFile(TranslationFileService $service): void
    {
        $this->authorizePermission('settings.languages.manage');
        $this->resetErrorBag('newPhpFile');

        try {
            $this->selectedResource = $service->createPhpResource($this->newPhpFile);
        } catch (InvalidArgumentException $exception) {
            $this->addError('newPhpFile', $exception->getMessage());
            return;
        }

        $this->newPhpFile = '';
        $this->refreshResources($service);
        $this->loadResource($service);

        session()->flash('status', 'Translation file created successfully.');
    }

    public function save(TranslationFileService $service): void
    {
        $this->authorizePermission('settings.languages.manage');
        if (!$this->persistRows($service)) {
            return;
        }

        $this->toast('Translation file updated successfully.');
    }

    public function render()
    {
        $all = collect($this->rows)
            ->map(fn(array $row, int $index) => [
                'index' => $index,
                'row' => $row,
            ])
            ->filter(function (array $entry) {
                $row = $entry['row'];
                $matchesSearch = $this->search === ''
                    || str_contains(mb_strtolower($row['key']), mb_strtolower($this->search))
                    || collect($row['values'])->contains(fn(string $value) => str_contains(mb_strtolower($value), mb_strtolower($this->search)));

                if (!$matchesSearch) {
                    return false;
                }

                if (!$this->showOnlyMissing) {
                    return true;
                }

                return collect($row['values'])->contains(fn(string $value) => trim($value) === '');
            })
            ->values()
            ->all();

        $total = count($all);
        $lastPage = max(1, (int) ceil($total / max(1, $this->perPage)));

        if ($this->page > $lastPage) {
            $this->page = $lastPage;
        }

        $offset = ($this->page - 1) * $this->perPage;
        $paged = array_slice($all, $offset, $this->perPage);

        return view('livewire.admin.setting.translations-page', [
            'resourceCount' => count($this->resources),
            'keyCount' => count($this->rows),
            'filteredRows' => $paged,
            'canManageTranslations' => $this->hasPermission('settings.languages.manage'),
            'page' => $this->page,
            'perPage' => $this->perPage,
            'lastPage' => $lastPage,
            'total' => $total,
        ]);
    }

    protected function loadResource(TranslationFileService $service): void
    {
        $payload = $service->read($this->selectedResource);
        $this->locales = $payload['locales'];
        $this->rows = $payload['rows'];
    }

    protected function refreshResources(TranslationFileService $service): void
    {
        $this->resources = $service->resources();
    }

    protected function resourceExists(string $key): bool
    {
        foreach ($this->resources as $resource) {
            if (($resource['key'] ?? null) === $key) {
                return true;
            }
        }

        return false;
    }

    protected function persistRows(TranslationFileService $service, ?int $focusIndex = null): bool
    {
        $normalizedRows = $this->normalizeRows($focusIndex);

        if ($normalizedRows === null) {
            return false;
        }

        $service->save($this->selectedResource, $normalizedRows);
        $this->refreshResources($service);
        $this->loadResource($service);

        return true;
    }

    protected function normalizeRows(?int $focusIndex = null): ?array
    {
        $this->resetErrorBag();

        $normalizedRows = [];
        $seenKeys = [];

        foreach ($this->rows as $index => $row) {
            $key = trim((string) ($row['key'] ?? ''));

            if ($key === '') {
                if ($focusIndex !== null && $focusIndex === $index) {
                    $this->addError('rows.' . $index . '.key', 'Translation key is required.');
                }

                continue;
            }

            $normalizedKey = mb_strtolower($key);

            if (isset($seenKeys[$normalizedKey])) {
                $this->addError('rows.' . $index . '.key', 'Translation keys must be unique within the file.');
                continue;
            }

            $seenKeys[$normalizedKey] = true;

            $values = [];
            foreach ($this->locales as $locale) {
                $values[$locale] = (string) ($row['values'][$locale] ?? '');
            }

            $normalizedRows[] = [
                'key' => $key,
                'values' => $values,
            ];
        }

        // if ($this->getErrorBag()->isNotEmpty()) {
        //     $this->toast(
        //         $focusIndex === null
        //         ? 'Could not save translations. Review the validation errors and try again.'
        //         : 'Could not save this translation row. Review the validation errors and try again.',
        //         'error'
        //     );

        //     return null;
        // }

        return $normalizedRows;
    }
}
