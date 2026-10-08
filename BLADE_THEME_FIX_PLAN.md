# Blade Theme Fix Plan — Agent Execution Guide

> Run agents **one by one in order**. Each agent has a clear scope, verification step, and
> hand-off. Do not skip verification before proceeding to the next agent.

---

## Root Cause Analysis

### Error: `View [pages.home.index] not found`

**Flow when a vendor activates their uploaded theme:**

```
Request hits tenant domain
  → IdentifyTenantTheme middleware
      → checks is_dir(storage/app/tenants/{id}/theme/views/)  ← symlink must exist
      → if dir exists → prepends path to view finder
  → ServeBladeThemeHome middleware
      → checks DB: activeBladeTheme() → theme is_active=true, status=approved
      → if active → calls StorefrontHomeController → view('pages.home.index')
      → view finder searches ALL registered paths for pages/home/index.blade.php
```

**The disconnect (root cause):**
`ServeBladeThemeHome` checks the **DB state** (active + approved).
`IdentifyTenantTheme` checks the **filesystem state** (symlink exists and is a dir).

If the symlink at `storage/app/tenants/{id}/theme/views/` is missing, broken, or was
never created (e.g., server restart, re-deploy cleared storage), the view path is never
prepended but `ServeBladeThemeHome` still intercepts the route → `View not found`.

### Additional Issues Found

1. **Starter kit download** — `BladeThemeStarterKitController::download()` streams the
   zip but does NOT validate that `resources/blade-theme-starter-kit/` exists first.
   If the directory is missing the server throws a generic 500.

2. **Starter kit zip structure** — The README says "zip the contents, not the folder".
   Many OS tools (macOS Finder, Windows right-click) wrap in a parent directory. The
   validator in `BladeThemeService::upload()` checks for `pages/home/index.blade.php`
   but will also receive `blade-theme-starter-kit/pages/home/index.blade.php` if the
   vendor zipped the folder itself. Validation fails with a confusing error.

3. **Blocked pattern `@php`** — `BladeThemeService::BLOCKED_PATTERNS` does NOT include
   `@php` even though the README says it's blocked. The README is wrong (or the
   implementation is wrong). Clarify and sync.

4. **`StorefrontHomeController` missing data** — It passes `$flash_sales` as a
   Collection but `homepage/sections/flash_sale.blade.php` in the starter kit uses
   `$flash_sales->isNotEmpty()` — that's fine. But the controller does not pass
   `$top_rated_products`, `$buy_together_products`, or `$paginated_products` that
   newer section templates may need.

5. **Starter kit section files use snake_case filenames** but `@include` calls must
   match exactly. Verify consistency.

6. **No recovery path** — If symlink is stale, there is no way for the system to
   self-heal. Need a guard in `ServeBladeThemeHome` or `IdentifyTenantTheme`.

---

## Agent 1 — Fix `ServeBladeThemeHome` + `IdentifyTenantTheme` Symlink Race

**File:** `app/Http/Middleware/ServeBladeThemeHome.php`  
**File:** `app/Http/Middleware/IdentifyTenantTheme.php`  
**File:** `app/Services/Tenant/BladeThemeService.php` (relinkLiveViews)

### What to do

**Step 1 — Guard in `ServeBladeThemeHome`:**

Before intercepting the route, verify the symlink/directory actually exists and is
readable. If DB says active but filesystem is missing, call `relinkLiveViews()` to
recreate the symlink, then proceed. If recreation fails, fall through to Livewire
`HomePage` instead of crashing.

```php
// ServeBladeThemeHome::handle() — add after $active check:
$themePath = $service->liveViewsPath((string) $tenant->getTenantKey());

if (!is_dir($themePath)) {
    // Symlink stale or missing — attempt self-heal
    try {
        $service->activateLatestApprovedForCurrentTenant((string) $tenant->getTenantKey());
    } catch (\Throwable) {
        // Fall through to default Livewire home
        return $next($request);
    }

    // Refresh active state after re-link
    $active = $service->activeBladeTheme((string) $tenant->getTenantKey());
    if (!$active || !is_dir($themePath)) {
        return $next($request);
    }
}
```

**Step 2 — Make `relinkLiveViews` public** (currently private):

Change `private function relinkLiveViews` → `public function relinkLiveViews` so
the middleware can call it directly.

**Step 3 — Add a health-check method to `BladeThemeService`:**

```php
public function isLiveViewsPathHealthy(string $tenantId): bool
{
    $path = $this->liveViewsPath($tenantId);
    return is_dir($path) && is_readable($path);
}
```

### Verification

```bash
# 1. Activate a blade theme in DB then manually break the symlink
rm storage/app/tenants/{testTenantId}/theme/views

# 2. Visit the storefront home — should NOT show 500
# 3. Should auto-recreate the symlink
ls -la storage/app/tenants/{testTenantId}/theme/
# views -> storage/app/{tenantId}/{version}  ← symlink recreated

# 4. Revisit home → should render the blade theme correctly
```

---

## Agent 2 — Fix `StorefrontHomeController` Data Contract

**File:** `app/Http/Controllers/Tenant/StorefrontHomeController.php`

### What to do

The controller currently passes a subset of the data that `HomePage.php` (Livewire)
computes. Align them so every variable a starter-kit section might use is available.

Add the missing variables (guard with null/empty defaults so existing themes don't break):

```php
$top_rated_products    = $repo->topRatedProducts(10)->getCollection();
$paginated_products    = $repo->paginatedProducts([], 10, 1)->getCollection();
$buy_together_products = collect();          // repo method may not exist yet — default to empty
$flash_banners         = $repo->activeBanners(); // re-use existing
$categories_with_children = $repo->rootCategoriesWithChildren();
```

Update `view('pages.home.index', compact(...))` to include all new variables.

Also: change the variable name `$flash_sales` to `$flashSales` to match the Livewire
component convention, OR keep both names via `compact()` + `array_merge()` so the
starter kit works with either convention. Document the chosen convention clearly in
`resources/blade-theme-starter-kit/README.md`.

### Verification

```bash
php artisan route:clear
php artisan view:clear
# Visit storefront home with blade theme active
# No "Undefined variable" errors in the rendered page
# All sections render (may be empty collections but no crash)
```

---

## Agent 3 — Fix Starter Kit Download + ZIP Structure

**File:** `app/Http/Controllers/Tenant/BladeThemeStarterKitController.php`

### What to do

**Step 1 — Guard against missing source directory:**

```php
if (!is_dir($sourceDir)) {
    abort(404, 'Starter kit not found.');
}
```

**Step 2 — Fix variable-name documentation in section files:**

Open every file in `resources/blade-theme-starter-kit/pages/home/sections/` and audit
the variable names used. Cross-reference with what `StorefrontHomeController` passes
(after Agent 2 fix). Correct any mismatches.

**Step 3 — Add auto-strip of top-level wrapper directory on upload:**

In `BladeThemeService::upload()`, after iterating all zip entries, detect if ALL
paths share a common top-level directory prefix (e.g., `blade-theme-starter-kit/`).
If so, strip that prefix from `$normalized` before validation and before extraction.

```php
// Detect common prefix
$prefix = $this->detectCommonPrefix($found);
if ($prefix !== '') {
    $found = array_map(fn($f) => substr($f, strlen($prefix)), $found);
}

// During extraction, strip prefix from destination path as well
```

Implement `detectCommonPrefix(array $paths): string` that returns the common leading
directory component followed by `/`, or `''` if paths are already at root level.

**Step 4 — Sync `README.md` blocked patterns with code:**

The README says `@php` is blocked; the code does NOT block it
(`BLOCKED_PATTERNS` only has `<?php`, `<?=`, raw PHP primitives).
Decide: add `@php` to `BLOCKED_PATTERNS` (safer) OR remove the claim from README.
Recommended: add `@php` to `BLOCKED_PATTERNS` since `@php` blocks can call the
same dangerous functions via Blade compilation.

### Verification

```bash
# 1. Download starter kit via browser/curl
curl -I http://vendor.dokan-v2.loc/admin/store/blade-theme/starter-kit
# → 200 with Content-Type: application/zip

# 2. Unzip and re-zip WITH the folder wrapper (simulate macOS zip behavior)
cd /tmp && cp blade-theme-starter-kit.zip test-wrapped.zip
# Edit the zip to have blade-theme-starter-kit/ prefix, then upload

# 3. Upload the wrapped zip — should succeed after prefix stripping
# 4. Upload the flat zip — should still succeed
# 5. Upload a zip with <?php — should be rejected
# 6. Upload a zip with @php — should now be rejected (if you added it to denylist)
```

---

## Agent 4 — Refactor Starter Kit to Match New Requirements

**Directory:** `resources/blade-theme-starter-kit/`

### Context

The platform now has:
- **Page Builder** (`app/Services/Tenant/PageBuilder/SectionRegistry.php`)
- **HomeVariant** sections system
- **TenantPageSection** records per tenant
- Multiple system themes (Elora, Souqify, Ecommet) with real section files

The starter kit is a **vendor-facing template**, not a system theme. It should
demonstrate best practices and wire to all the data the platform provides, but it
does NOT need to implement `SectionRegistry` (that's for system themes). However,
it must remain compatible with how `StorefrontHomeController` passes data.

### What to do

**Step 1 — Audit all section files vs controller data contract (post-Agent-2):**

For each section file, verify every `$variable` used is provided by the controller.
Create a variable reference table in `README.md`:

| Variable | Type | Description |
|----------|------|-------------|
| `$storeName` | string | Store display name |
| `$logoPath` | string\|null | Logo URL |
| `$banners` | Collection | Active hero banners |
| `$flash_sales` | Collection | Active flash sales |
| `$new_arrivals` | Collection | New arrival products (up to 10) |
| `$recommended_products` | Collection | Recommended products |
| `$best_sellers` | Collection | Best selling products |
| `$trending_products` | Collection | Trending now products |
| `$featured_products` | Collection | Featured products |
| `$top_rated_products` | Collection | Top rated products |
| `$categories` | Collection | All active categories |
| `$rootCategories` | Collection | Root categories with children |
| `$currentCurrency` | object | Current currency |
| `$socialLinks` | array | Social media links |
| `$footerText` | string | Footer body text |
| `$footerCopyright` | string | Footer copyright line |

**Step 2 — Add missing section stubs:**

The starter kit is missing sections that the system themes support. Add stub sections:
- `pages/home/sections/top_rated.blade.php`
- `pages/home/sections/trending_now.blade.php`
- `pages/home/sections/featured.blade.php`

Each stub should render a basic product grid using `$top_rated_products`,
`$trending_products`, `$featured_products` respectively.

**Step 3 — Update `pages/home/index.blade.php`** to include all new sections
conditionally (check `->isNotEmpty()` before including).

**Step 4 — Update `layout/app.blade.php`** to expose `$currentCurrency` and
`$socialLinks` in the header/footer via `@include('partials.header')` —
pass them as `@include('partials.header', ['currentCurrency' => $currentCurrency])`.

**Step 5 — Update `partials/header.blade.php` and `partials/footer.blade.php`**
to use `$socialLinks`, `$footerText`, `$footerCopyright` from the passed variables.

**Step 6 — Update `README.md`** with:
- Complete variable reference table (from Step 1)
- Section file map
- Clear zip instructions with screenshots/ASCII diagram showing correct zip structure
- Note that `@php` tags are now blocked

### Verification

```bash
# Download fresh starter kit
curl -o /tmp/sk-test.zip http://vendor.dokan-v2.loc/admin/store/blade-theme/starter-kit

# Check zip structure (should be flat, no wrapper dir)
unzip -l /tmp/sk-test.zip | head -30
# Expect: layout/app.blade.php, pages/home/index.blade.php, etc. — NO prefix

# Upload the downloaded zip without modification
# Admin: approve it
# Vendor: activate it from Store → Themes
# Visit storefront home → all sections render, no errors
```

---

## Agent 5 — End-to-End Test Suite

**File:** `tests/Feature/Tenant/BladeThemeTest.php` (create or extend)

### What to do

Write feature tests covering the full lifecycle:

```php
class BladeThemeTest extends TestCase
{
    // Test 1: Upload flat zip → passes validation → status=pending
    public function test_upload_valid_flat_zip_creates_pending_theme()

    // Test 2: Upload wrapped zip (with folder prefix) → strips prefix → passes
    public function test_upload_zip_with_wrapper_directory_is_accepted()

    // Test 3: Upload zip missing required file → rejected
    public function test_upload_zip_missing_required_file_is_rejected()

    // Test 4: Upload zip with <?php → rejected
    public function test_upload_zip_with_raw_php_is_rejected()

    // Test 5: Upload zip with @php → rejected (after denylist update)
    public function test_upload_zip_with_blade_php_directive_is_rejected()

    // Test 6: Approve theme → symlink created
    public function test_approve_theme_creates_live_views_symlink()

    // Test 7: Activate theme → storefront home renders without error
    public function test_active_blade_theme_renders_home_page()

    // Test 8: Broken symlink → self-heals on next request
    public function test_broken_symlink_is_recreated_on_next_request()

    // Test 9: Deactivate theme → symlink removed → Livewire home used
    public function test_deactivate_removes_symlink_and_falls_back_to_livewire()

    // Test 10: Starter kit download returns valid zip
    public function test_starter_kit_download_returns_valid_zip_with_required_files()
}
```

Each test should:
1. Create a test tenant
2. Build a minimal in-memory zip using `ZipArchive`
3. Make the appropriate HTTP request
4. Assert DB state + filesystem state

### Verification

```bash
php artisan test tests/Feature/Tenant/BladeThemeTest.php
# All 10 tests green
```

---

## Agent 6 — Admin Panel: Blade Theme Queue Page Review

**File:** `app/Livewire/Admin/Setting/BladeThemeQueuePage.php`

### What to do

Review the admin queue page for:
1. Does it show the correct status for pending/approved/rejected themes?
2. Does the approve button call `BladeThemeService::approve()` correctly?
3. Does approval automatically re-link the symlink for already-activated tenants?

**Gap found:** `BladeThemeService::approve()` only updates DB status. It does NOT
call `relinkLiveViews()`. If a tenant activates their theme and it gets rejected,
then re-uploaded and approved, the symlink is never recreated until the tenant
manually deactivates + re-activates.

**Fix:** After approval, if `is_active=true` on the newly approved theme, call
`relinkLiveViews($tenantId)`.

```php
public function approve(int $themeId, string $reviewerName): void
{
    $theme = BladeTheme::query()->findOrFail($themeId);
    $theme->update([
        'status' => BladeTheme::STATUS_APPROVED,
        'rejection_reason' => null,
        'reviewed_at' => now(),
        'reviewed_by' => $reviewerName,
    ]);

    // Self-heal: re-link if this theme is already the active one
    if ($theme->is_active) {
        $this->relinkLiveViews($theme->tenant_id);
    }
}
```

### Verification

```bash
# 1. Upload theme → admin rejects → admin approves same version
# 2. Tenant activates → symlink exists → home renders
# 3. Check: no 500 errors during any step
```

---

## Agent 7 — Final Integration Smoke Test

**Manual QA checklist** (run in staging with a real tenant):

```
[ ] 1. Visit /admin/store/blade-theme — page loads, table shows (empty or with themes)
[ ] 2. Click "Download Starter Kit" — .zip downloads without error
[ ] 3. Unzip the starter kit — structure is flat (no wrapper folder)
[ ] 4. Re-zip the contents (flat) — upload via the panel
[ ] 5. Success toast: "Theme uploaded and queued for admin review"
[ ] 6. Central admin: visit /admin/blade-themes — pending theme appears
[ ] 7. Admin approves the theme
[ ] 8. Tenant: visit Store → Themes — "Custom Theme" card appears
[ ] 9. Tenant activates "Custom Theme"
[ ] 10. Visit storefront home (https://zazo.nogrgr.com) — renders with blade theme, no errors
[ ] 11. Check all sections: hero, trust bar, flash sale, categories, new arrivals, recommended, best sellers
[ ] 12. Deactivate blade theme → storefront falls back to previous system theme
[ ] 13. Re-activate → blade theme re-appears without error
[ ] 14. Break symlink manually → visit storefront → auto-heals, page renders
[ ] 15. Upload zip with parent folder wrapper → upload succeeds
[ ] 16. Upload zip with <?php → upload rejected with clear error message
```

---

## Summary of Files Changed

| Agent | File | Change Type |
|-------|------|-------------|
| 1 | `app/Http/Middleware/ServeBladeThemeHome.php` | Guard + self-heal symlink |
| 1 | `app/Services/Tenant/BladeThemeService.php` | Make `relinkLiveViews` public + add health-check |
| 2 | `app/Http/Controllers/Tenant/StorefrontHomeController.php` | Add missing variables |
| 3 | `app/Http/Controllers/Tenant/BladeThemeStarterKitController.php` | Guard 404 on missing dir |
| 3 | `app/Services/Tenant/BladeThemeService.php` | Strip wrapper prefix on upload |
| 3 | `resources/blade-theme-starter-kit/README.md` | Sync blocked patterns, add variable table |
| 4 | `resources/blade-theme-starter-kit/**` | Add missing sections, fix variable names |
| 5 | `tests/Feature/Tenant/BladeThemeTest.php` | New test suite |
| 6 | `app/Services/Tenant/BladeThemeService.php` | Re-link on approve if already active |
| 6 | `app/Livewire/Admin/Setting/BladeThemeQueuePage.php` | Review + wire approval correctly |

---

## Priority Order

1. **Agent 1** — Fix the crash (P0, unblocks everything)
2. **Agent 2** — Fix data contract (P0, prevents runtime variable errors)
3. **Agent 3** — Fix upload + download (P1, unblocks vendor onboarding)
4. **Agent 6** — Fix admin approval flow (P1, closes the re-link gap)
5. **Agent 4** — Refactor starter kit (P2, improves developer experience)
6. **Agent 5** — Tests (P2, regression protection)
7. **Agent 7** — Smoke test (P3, sign-off gate)
