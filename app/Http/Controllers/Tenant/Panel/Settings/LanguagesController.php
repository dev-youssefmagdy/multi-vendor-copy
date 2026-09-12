<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Language;
use App\Models\Tenant\LanguagePurchase;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class LanguagesController extends PanelController
{
    public function __construct(private readonly TenantPanelRepository $repo)
    {
    }

    public function index(): View
    {
        return view('tenant.pages.settings.languages.index', [
            'columns' => [
                TableColumn::make('language', 'Language')->orderable(false),
                TableColumn::make('direction', 'Direction')->orderable(false),
                TableColumn::make('type', 'Type')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $languages = $this->repo->languages();
        $purchasedIds = LanguagePurchase::query()->pluck('central_language_id')->all();

        $centralLanguages = CentralLanguage::query()
            ->whereIn('id', $languages->pluck('central_language_id')->filter()->unique()->values()->all())
            ->get(['id', 'is_free'])
            ->keyBy('id');

        return DataTables::collection($languages)
            ->addIndexColumn()
            ->editColumn('language', fn (Language $language) => view('tenant.pages.settings.languages._cols.language', ['language' => $language])->render())
            ->editColumn('direction', fn (Language $language) => strtoupper($language->direction->value))
            ->editColumn('type', function (Language $language) use ($purchasedIds, $centralLanguages) {
                $isPurchased = $language->central_language_id && in_array($language->central_language_id, $purchasedIds, true);
                $isFree = !$language->central_language_id || !$isPurchased
                    ? ($centralLanguages->get($language->central_language_id)?->is_free ?? true)
                    : false;

                return $isFree
                    ? '<span class="badge badge-green">Free</span>'
                    : '<span class="badge badge-violet">Purchased</span>';
            })
            ->editColumn('status', fn (Language $language) => $language->is_default
                ? '<span class="badge badge-green">Default</span>'
                : ($language->is_active ? '<span class="badge badge-cyan">Active</span>' : '<span class="badge badge-amber">Inactive</span>'))
            ->addColumn('actions', fn (Language $language) => view('tenant.pages.settings.languages._cols.actions', ['language' => $language])->render())
            ->rawColumns(['language', 'type', 'status', 'actions'])
            ->toJson();
    }

    public function toggleActive(Language $language): JsonResponse
    {
        $language->update(['is_active' => !$language->is_active]);

        return $this->success(__('Language state updated successfully.'));
    }

    public function makeDefault(Language $language, TenantPanelService $service): JsonResponse
    {
        $service->markDefaultLanguage($language);

        return $this->success(__('Default language updated successfully.'));
    }
}
