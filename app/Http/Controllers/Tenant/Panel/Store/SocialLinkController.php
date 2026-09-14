<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SaveSocialLinkRequest;
use App\Models\Tenant\SocialLink;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;

final class SocialLinkController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    /** Fragment endpoint: rebuilds the client-mode datatable rows after a create/update/delete without a full page reload. */
    public function index(): JsonResponse
    {
        $rows = $this->repo->socialLinks()->map(fn (SocialLink $link) => [
            view('tenant.pages.store.appearance._cols.social-icon', ['link' => $link])->render(),
            view('tenant.pages.store.appearance._cols.social-url', ['link' => $link])->render(),
            e($link->serial_number),
            view('tenant.pages.store.appearance._cols.social-actions', ['link' => $link])->render(),
        ])->values()->all();

        return response()->json(['data' => ['rows' => $rows]]);
    }

    public function show(SocialLink $link): JsonResponse
    {
        return response()->json(['data' => [
            'icon' => $link->icon->name,
            'url' => $link->url,
            'serial_number' => $link->serial_number,
        ]]);
    }

    public function store(SaveSocialLinkRequest $request): JsonResponse
    {
        $this->service->saveSocialLink($request->validated());

        return $this->success('Social link saved successfully.');
    }

    public function validateStore(SaveSocialLinkRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function update(SaveSocialLinkRequest $request, SocialLink $link): JsonResponse
    {
        $this->service->saveSocialLink($request->validated(), $link);

        return $this->success('Social link saved successfully.');
    }

    public function validateUpdate(SaveSocialLinkRequest $request, SocialLink $link): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroy(SocialLink $link): JsonResponse
    {
        $link->delete();

        return $this->success('Social link deleted.');
    }
}
