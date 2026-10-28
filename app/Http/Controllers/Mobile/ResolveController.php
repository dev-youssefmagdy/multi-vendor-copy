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
        $notFound = fn () => response()->json(['success' => false, 'message' => 'Store not found.', 'code' => 'not_found', 'data' => ['status' => 'not_found']], 404);

        $host = $this->normalise((string) $request->query('host', ''));
        $code = trim((string) $request->query('code', ''));
        $tenant = null;
        $domainName = $host;

        if ($host !== '') {
            $domain = Domain::query()->where('domain', $host)->first();
            $tenant = $domain ? Tenant::find($domain->tenant_id) : null;
        } elseif ($code !== '' && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $code)) {
            // Store code = the tenant id (also the path slug), or an optional `store_code`
            // value kept in the tenant data JSON (no schema change; set it via Tenant::update).
            $tenant = Tenant::find($code) ?? Tenant::query()->where('data->store_code', $code)->first();
            $domainName = (string) ($tenant?->domains()->orderBy('id')->value('domain') ?? '');
        }

        if (!$tenant) {
            return $notFound();
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
            'domain' => $domainName,
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
