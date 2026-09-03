<?php

namespace App\Livewire\Admin\Cache;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Services\CacheAdminService;
use Illuminate\Support\Facades\DB;

class MainCachePage extends ContentPage
{
    use InteractsWithAdminUi;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Main Cache',
            'badge' => 'Operations',
            'description' => 'Control cache clearing for the central application environment.',
            'bullets' => [
                'Expose clear config, route, view, and application cache commands.',
                'Require explicit confirmation before destructive maintenance actions.',
                'Show success and failure toast responses through SweetAlert.',
            ],
        ];
    }

    public function requestClearMain(): void
    {
        $this->authorizePermission('system.cache.manage');
        $this->confirmAction('clearMain', [], [
            'title' => 'Clear main cache?',
            'text' => 'This will clear optimized caches for the central application.',
            'confirmButtonText' => 'Clear cache',
        ]);
    }

    public function clearMain(CacheAdminService $service): void
    {
        $this->authorizePermission('system.cache.manage');
        $service->clearMain();
        $this->toast('Main cache cleared successfully.');
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'cards' => [
                ['label' => 'Sessions', 'value' => number_format(DB::table('sessions')->count()), 'caption' => 'Current session rows', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Jobs', 'value' => number_format(DB::table('jobs')->count()), 'caption' => 'Queued jobs waiting to run', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'actions' => [
                ['label' => 'Clear Main Cache', 'method' => 'requestClearMain', 'variant' => 'btn-primary'],
            ],
        ]);
    }
}
