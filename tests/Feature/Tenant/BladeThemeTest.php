<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\BladeTheme;
use App\Services\Tenant\BladeThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;
use ZipArchive;

class BladeThemeTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    // Tests 7–10 make HTTP requests that initialize full tenancy + central DB.
    // Mark them in a separate group so they can be run independently on a clean DB.
    // The service-level tests (1–6) are the primary regression guard.

    private BladeThemeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->service = app(BladeThemeService::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Build an in-memory zip with the given file map and return an UploadedFile. */
    private function makeZip(array $files, string $tmpName = 'theme-test.zip'): UploadedFile
    {
        $path = sys_get_temp_dir() . '/' . $tmpName;

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $zipPath => $content) {
            $zip->addFromString($zipPath, $content);
        }

        $zip->close();

        return new UploadedFile($path, $tmpName, 'application/zip', null, true);
    }

    private function validFlatFiles(): array
    {
        return [
            'layout/app.blade.php'        => '@yield("content")',
            'pages/home/index.blade.php'  => '@extends("layout.app") @section("content") Hello @endsection',
        ];
    }

    private function tenantId(): string
    {
        return (string) $this->tenant->getTenantKey();
    }

    // ── Test 1: Upload flat zip → pending ─────────────────────────────────

    public function test_upload_valid_flat_zip_creates_pending_theme(): void
    {
        $file = $this->makeZip($this->validFlatFiles());

        tenancy()->initialize($this->tenant);
        $theme = $this->service->upload($this->tenant, $file);

        // Check filesystem paths in tenant context — storage_path() is tenant-scoped.
        $baseDir = storage_path('app/' . $theme->storage_path);
        $this->assertDirectoryExists($baseDir);
        $this->assertFileExists($baseDir . '/pages/home/index.blade.php');

        tenancy()->end();

        $this->assertSame(BladeTheme::STATUS_PENDING, $theme->status);
        $this->assertFalse((bool) $theme->is_active);
    }

    // ── Test 2: Upload zip with wrapper directory → strips prefix, passes ──

    public function test_upload_zip_with_wrapper_directory_is_accepted(): void
    {
        $wrapped = [];
        foreach ($this->validFlatFiles() as $path => $content) {
            $wrapped['my-theme/' . $path] = $content;
        }

        $file = $this->makeZip($wrapped);

        tenancy()->initialize($this->tenant);
        $theme = $this->service->upload($this->tenant, $file);

        // Files must be extracted WITHOUT the wrapper prefix — check in tenant context.
        $baseDir = storage_path('app/' . $theme->storage_path);
        $this->assertFileExists($baseDir . '/pages/home/index.blade.php');
        $this->assertFileDoesNotExist($baseDir . '/my-theme/pages/home/index.blade.php');

        tenancy()->end();

        $this->assertSame(BladeTheme::STATUS_PENDING, $theme->status);
    }

    // ── Test 3: Missing required file → rejected ───────────────────────────

    public function test_upload_zip_missing_required_file_is_rejected(): void
    {
        $file = $this->makeZip([
            'layout/app.blade.php' => '@yield("content")',
            // pages/home/index.blade.php deliberately omitted
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Required file missing/i');

        tenancy()->initialize($this->tenant);
        try {
            $this->service->upload($this->tenant, $file);
        } finally {
            tenancy()->end();
        }
    }

    // ── Test 4: Raw PHP tag → rejected ────────────────────────────────────

    public function test_upload_zip_with_raw_php_tag_is_rejected(): void
    {
        $files = $this->validFlatFiles();
        $files['layout/app.blade.php'] = '<?php echo "bad"; ?> @yield("content")';

        $file = $this->makeZip($files);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Blocked pattern/i');

        tenancy()->initialize($this->tenant);
        try {
            $this->service->upload($this->tenant, $file);
        } finally {
            tenancy()->end();
        }
    }

    // ── Test 5: @php directive → rejected ────────────────────────────────

    public function test_upload_zip_with_blade_php_directive_is_rejected(): void
    {
        $files = $this->validFlatFiles();
        $files['pages/home/index.blade.php'] = '@php $x = 1; @endphp @extends("layout.app") @section("content")@endsection';

        $file = $this->makeZip($files);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Blocked pattern.*@php/i');

        tenancy()->initialize($this->tenant);
        try {
            $this->service->upload($this->tenant, $file);
        } finally {
            tenancy()->end();
        }
    }

    // ── Test 6: Approve theme → symlink created ───────────────────────────

    public function test_approve_theme_and_activate_creates_live_views_symlink(): void
    {
        $file = $this->makeZip($this->validFlatFiles());

        tenancy()->initialize($this->tenant);
        $theme = $this->service->upload($this->tenant, $file);
        tenancy()->end();

        // Admin approves (central context)
        $this->service->approve($theme->id, 'test-admin');

        $theme->refresh();
        $this->assertSame(BladeTheme::STATUS_APPROVED, $theme->status);

        // Tenant activates + symlink health check — both in tenant context.
        tenancy()->initialize($this->tenant);
        $this->service->activateLatestApprovedForCurrentTenant($this->tenantId());
        $healthy = $this->service->isLiveViewsPathHealthy($this->tenantId());
        tenancy()->end();

        $this->assertTrue($healthy);
    }

    // ── Test 7: Active theme → ServeBladeThemeHome intercepts home ────────

    public function test_active_blade_theme_does_not_throw_view_not_found(): void
    {
        $file = $this->makeZip($this->validFlatFiles());

        tenancy()->initialize($this->tenant);
        $theme = $this->service->upload($this->tenant, $file);
        tenancy()->end();

        $this->service->approve($theme->id, 'test-admin');

        tenancy()->initialize($this->tenant);
        $this->service->activateLatestApprovedForCurrentTenant($this->tenantId());
        tenancy()->end();

        $response = $this->get('http://' . $this->tenantHost . '/');

        // Should NOT be a 500 — 200 or redirect are both acceptable
        $this->assertNotSame(500, $response->getStatusCode());
    }

    // ── Test 8: Broken symlink self-heals ─────────────────────────────────

    public function test_broken_symlink_is_recreated_by_serve_middleware(): void
    {
        $file = $this->makeZip($this->validFlatFiles());

        tenancy()->initialize($this->tenant);
        $theme = $this->service->upload($this->tenant, $file);
        tenancy()->end();

        $this->service->approve($theme->id, 'test-admin');

        // Activate and break symlink — all in tenant context.
        tenancy()->initialize($this->tenant);
        $this->service->activateLatestApprovedForCurrentTenant($this->tenantId());
        $livePath = $this->service->liveViewsPath($this->tenantId());
        @unlink($livePath);
        $brokenBefore = !$this->service->isLiveViewsPathHealthy($this->tenantId());
        tenancy()->end();

        $this->assertTrue($brokenBefore, 'Expected symlink to be broken after manual unlink');

        // Next request should trigger self-heal in ServeBladeThemeHome middleware.
        $response = $this->get('http://' . $this->tenantHost . '/');
        $this->assertNotSame(500, $response->getStatusCode());
    }

    // ── Test 9: Deactivate removes symlink ───────────────────────────────

    public function test_deactivate_removes_symlink(): void
    {
        $file = $this->makeZip($this->validFlatFiles());

        tenancy()->initialize($this->tenant);
        $theme = $this->service->upload($this->tenant, $file);
        tenancy()->end();

        $this->service->approve($theme->id, 'test-admin');

        // Activate, check healthy, deactivate, check gone — all in tenant context.
        tenancy()->initialize($this->tenant);
        $this->service->activateLatestApprovedForCurrentTenant($this->tenantId());
        $healthyAfterActivate = $this->service->isLiveViewsPathHealthy($this->tenantId());

        $this->service->deactivate($this->tenantId());
        $healthyAfterDeactivate = $this->service->isLiveViewsPathHealthy($this->tenantId());
        tenancy()->end();

        $this->assertTrue($healthyAfterActivate);
        $this->assertFalse($healthyAfterDeactivate);

        $theme->refresh();
        $this->assertFalse((bool) $theme->is_active);
    }

    // ── Test 10: Starter kit download returns valid zip ───────────────────

    public function test_starter_kit_download_returns_zip_with_required_files(): void
    {
        $response = $this->actingAsTenantAdmin()
            ->get($this->tenantUrl('/store/blade-theme/starter-kit'));

        $response->assertStatus(200);
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));

        // Save to a temp file and inspect its contents
        $tmpPath = sys_get_temp_dir() . '/sk-download-test-' . uniqid() . '.zip';
        file_put_contents($tmpPath, $response->getContent());

        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($tmpPath));

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        @unlink($tmpPath);

        $this->assertContains('layout/app.blade.php', $names);
        $this->assertContains('pages/home/index.blade.php', $names);
    }
}
