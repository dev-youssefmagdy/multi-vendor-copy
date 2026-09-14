<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SavePageRequest;
use App\Models\Tenant\Page;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PageFormController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ($redirect = PagesController::ensurePaymentGatewayActive()) {
            return $redirect;
        }

        return $this->form();
    }

    public function edit(Page $page): View|RedirectResponse
    {
        if ($redirect = PagesController::ensurePaymentGatewayActive()) {
            return $redirect;
        }

        return $this->form($page);
    }

    private function form(?Page $page = null): View
    {
        $languages = $this->repo->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $translations = $languages->mapWithKeys(fn ($language) => [$language->code => ['title' => '', 'body' => '']])->all();

        if ($page) {
            $page->load('translations.language');
            $translations = array_replace_recursive($translations, $page->translationsByLocale(['title', 'body']));
        }

        return view('tenant.pages.store.pages.form', [
            'page' => $page,
            'pageTitle' => $page ? 'Edit Page' : 'Add Page',
            'pageDescription' => $page
                ? 'Update storefront page settings and translated content.'
                : 'Create a new storefront page with translated content for tenant languages.',
            'languages' => $languages,
            'activeLocale' => $defaultLocale,
            'translations' => $translations,
        ]);
    }

    public function store(SavePageRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SavePageRequest $request, Page $page): JsonResponse
    {
        return $this->save($request, $page);
    }

    private function save(SavePageRequest $request, ?Page $page): JsonResponse
    {
        $validated = $request->validated();

        $saved = $this->service->savePage([
            'slug' => $validated['slug'] ?? null,
            'active' => $validated['active'] ?? false,
            'default_locale' => $request->defaultLocale(),
            'translations' => $validated['translations'] ?? [],
        ], $page);

        return $this->success(
            $page ? 'Page updated successfully.' : 'Page created successfully.',
            redirect: route('tenant.store.pages.edit', $saved),
        );
    }

    public function validateStore(SavePageRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SavePageRequest $request, Page $page): JsonResponse
    {
        return $this->validFormResponse();
    }
}
