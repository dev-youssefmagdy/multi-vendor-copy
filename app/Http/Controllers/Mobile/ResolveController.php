<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

/** GET /resolve?host=  (central, throttled): registered domain -> tenant summary. */
class ResolveController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $host = $this->normalise((string) ($request->query('host') ?? $request->query('code') ?? ''));

        if ($host === '') {
            return response()->json(['success' => false, 'message' => 'host is required.', 'data' => ['status' => 'not_found']], 422);
        }

        $domain = Domain::query()->where('domain', $host)->first();
        /** @var Tenant|null $tenant */
        $tenant = $domain ? Tenant::find($domain->tenant_id) : null;

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Store not found.', 'data' => ['status' => 'not_found']], 404);
        }

        $status = $tenant->status === TenantStatus::Active ? 'active' : 'suspended';
        $name = (string) ($tenant->shop_name ?? '');
        $logo = null;

        if ($status === 'active') {
            [$name, $logo] = $tenant->run(function () {
                $repo = app(StorefrontRepository::class);

                return [$repo->storeName(), $repo->resolvedLogo()['image_url'] ?? null];
            });
        }

        return response()->json(['success' => true, 'data' => [
            'tenant_id' => (string) $tenant->getKey(),
            'domain' => $host,
            'name' => $name,
            'logo' => $logo,
            'status' => $status,
            // Always the platform's own API origin; never derived from the request input.
            'api_base' => rtrim((string) config('app.url'), '/').'/api/mobile/v1',
        ]]);
    }

    private function normalise(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value);
        $value = explode('/', $value, 2)[0];
        $value = explode(':', $value, 2)[0];

        return preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $value) ? $value : '';
    }
}
