<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Services\AppSettingService;
use App\Services\TranslationFileService;
use Livewire\Component;

class TranslationKeyAccessPage extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;

    public string $search = '';
    public int $page = 1;
    public int $perPage = 25;

    public array $lockedKeys = [];

    protected $queryString = ['page'];

    public function mount(): void
    {
        $raw = app(\App\Repositories\AppSettingRepository::class)
            ->getByKeys(['translation_locked_keys'])
            ->get('translation_locked_keys')?->value;

        $decoded = json_decode((string) $raw, true);
        $this->lockedKeys = is_array($decoded) ? $decoded : [];
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, (int) $page);
    }

    public function toggleLock(string $key, AppSettingService $service): void
    {
        $this->authorizePermission('settings.languages.manage');

        if (in_array($key, $this->lockedKeys, true)) {
            $this->lockedKeys = array_values(array_diff($this->lockedKeys, [$key]));
        } else {
            $this->lockedKeys[] = $key;
        }

        $this->persist($service);
        $this->toast('Translation key access updated successfully.');
    }

    protected function persist(AppSettingService $service): void
    {
        $service->saveMany([
            'translation_locked_keys' => [
                'value' => json_encode(array_values(array_unique($this->lockedKeys))),
                'type' => 'string',
                'is_public' => false,
            ],
        ]);
    }

    public function render(TranslationFileService $translationFiles)
    {
        $allKeys = collect($translationFiles->resources())
            ->flatMap(fn(array $resource) => collect($translationFiles->read((string) $resource['key'])['rows'] ?? [])
                ->pluck('key'))
            ->unique()
            ->sort()
            ->values();

        $filtered = $this->search === ''
            ? $allKeys
            : $allKeys->filter(fn(string $key) => str_contains(mb_strtolower($key), mb_strtolower($this->search)))->values();

        $total = $filtered->count();
        $lastPage = max(1, (int) ceil($total / max(1, $this->perPage)));

        if ($this->page > $lastPage) {
            $this->page = $lastPage;
        }

        $paged = $filtered->slice(($this->page - 1) * $this->perPage, $this->perPage)->values();

        $rows = $paged->map(fn(string $key) => [
            'key' => $key,
            'locked' => in_array($key, $this->lockedKeys, true),
        ])->all();

        return view('livewire.admin.setting.translation-key-access-page', [
            'rows' => $rows,
            'total' => $total,
            'lockedCount' => count($this->lockedKeys),
            'keyCount' => $allKeys->count(),
            'page' => $this->page,
            'perPage' => $this->perPage,
            'lastPage' => $lastPage,
            'canManage' => $this->hasPermission('settings.languages.manage'),
        ]);
    }
}
