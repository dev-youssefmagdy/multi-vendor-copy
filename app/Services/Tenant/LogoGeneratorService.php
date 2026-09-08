<?php

namespace App\Services\Tenant;

use App\Models\AppSetting;
use App\Models\Tenant as TenantModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogoGeneratorService
{
    private string $baseUrl;
    private string $token;
    private int $timeout;

    public function __construct()
    {
        $cfg = config('extra_service.python_service');
        $this->baseUrl = rtrim((string) ($cfg['url'] ?? 'http://127.0.0.1:8008'), '/');
        $this->token = (string) ($cfg['token'] ?? '');
        $this->timeout = (int) ($cfg['timeout'] ?? 60);
    }

    /**
     * The central-configured maximum number of AI logo generations per store,
     * or null if unlimited (setting blank/0/missing).
     */
    public function limit(): ?int
    {
        $value = tenancy()->central(
            fn() => AppSetting::query()->where('key', 'ai_logo_generation_limit')->value('value')
        );

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
    }

    public function usedCount(): int
    {
        $tenant = tenant();
        if (!$tenant instanceof TenantModel) {
            return 0;
        }

        return (int) (tenancy()->central(
            fn() => TenantModel::query()->find($tenant->getTenantKey())?->data['ai_logo_generation_count'] ?? 0
        ) ?? 0);
    }

    public function remaining(): ?int
    {
        $limit = $this->limit();

        return $limit === null ? null : max(0, $limit - $this->usedCount());
    }

    public function hasReachedLimit(): bool
    {
        $limit = $this->limit();

        return $limit !== null && $this->usedCount() >= $limit;
    }

    /**
     * Call the Python /generate-logo endpoint and persist the image to public
     * storage, returning the tenant-asset URL (same format used by uploadLogo).
     *
     * @param  array{store_name: string, tagline?: string, category?: string, style?: string, colors?: string}  $params
     * @return string  Public URL of the saved logo.
     *
     * @throws \RuntimeException  On HTTP error, missing image data, or when the store has reached its AI logo generation limit.
     */
    public function generate(array $params): string
    {
        if ($this->hasReachedLimit()) {
            throw new \RuntimeException('You have reached the maximum number of AI logo generations allowed for your store.');
        }

        $headers = ['Accept' => 'application/json'];
        if ($this->token !== '') {
            $headers['X-Service-Token'] = $this->token;
        }

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/generate-logo", [
                'store_name' => $params['store_name'] ?? '',
                'tagline' => $params['tagline'] ?? null,
                'category' => $params['category'] ?? 'general',
                'style' => $params['style'] ?? 'modern',
                'colors' => $params['colors'] ?? null,
            ]);

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            Log::error('LogoGeneratorService: Python service error', [
                'status' => $response->status(),
                'detail' => $detail,
            ]);
            throw new \RuntimeException("Logo generation failed: {$detail}");
        }

        $b64 = $response->json('image_b64');
        if (empty($b64)) {
            throw new \RuntimeException('Logo generation returned empty image data.');
        }

        // Decode and store the PNG in public storage
        $imageData = base64_decode($b64, strict: true);
        if ($imageData === false) {
            throw new \RuntimeException('Logo generation returned invalid base64 data.');
        }

        $filename = 'appearances/logos/ai_' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $imageData);

        $this->incrementUsedCount();

        return tenant_asset($filename);
    }

    private function incrementUsedCount(): void
    {
        $tenant = tenant();
        if (!$tenant instanceof TenantModel) {
            return;
        }

        tenancy()->central(function () use ($tenant) {
            $tenantRecord = TenantModel::query()->find($tenant->getTenantKey());
            if (!$tenantRecord) {
                return;
            }

            $data = $tenantRecord->data ?? [];
            $data['ai_logo_generation_count'] = (int) ($data['ai_logo_generation_count'] ?? 0) + 1;
            $tenantRecord->update(['data' => $data]);
        });
    }
}
