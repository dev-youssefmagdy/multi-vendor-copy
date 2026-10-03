# Footer Logo Feature — Implementation Plan

## Goal
Allow tenants to set a **separate footer logo** (AR + EN image uploads, plus a recommended width field) independent of the header logo. The footer logo settings live inside the existing **Footer tab** on Appearance → General. Existing tenants default to their current header logo values.

---

## New Settings Keys

| Key | Type | Default (existing tenants) | Notes |
|-----|------|---------------------------|-------|
| `footer_logo_path_ar` | string | copy of `logo_path_ar` | AR footer image URL |
| `footer_logo_path_en` | string | copy of `logo_path_en` | EN footer image URL |
| `footer_logo_width` | string | `''` | e.g. `"160"` (px). Empty = no explicit width |

No separate `footer_logo_mode` — the footer logo always shows an image if a path is saved; falls back to the header logo (same `resolvedLogo()`) when both paths are empty.

---

## Files to Change

### 1. `app/Repositories/Tenant/StorefrontRepository.php`
- Add `footer_logo_path_ar`, `footer_logo_path_en`, `footer_logo_width` to the `appearanceSettings()` `whereIn` list.
- Add new public method `resolvedFooterLogo(): array` — returns footer-specific image paths if set, falls back to `resolvedLogo()` result. Also includes `width` from `footer_logo_width`.

### 2. `resources/views/components/storefront-footer-logo.blade.php` *(new file)*
- New Blade component `<x-storefront-footer-logo>` — calls `resolvedFooterLogo()`, renders `<img>` with optional inline width style, or falls back to text logo like `storefront-logo` does.

### 3. `resources/views/themes/*/partials/footer*.blade.php` — 9 files that use `<x-storefront-logo>`
Replace `<x-storefront-logo …>` with `<x-storefront-footer-logo …>` in:
- `themes/souqify/partials/footer.blade.php`
- `themes/souqify/partials/footer-v2.blade.php`
- `themes/souqify/partials/footer-v3.blade.php`
- `themes/souqify/partials/footer-v4.blade.php`
- `themes/souqify/partials/footer-v5.blade.php`
- `themes/souqify/partials/footer-v6.blade.php`
- `themes/elora/partials/footer.blade.php`
- `themes/elora/partials/footer-v2.blade.php`
- `themes/elora/partials/footer-v6.blade.php`
- `themes/ecommet/partials/footer.blade.php`

### 4. `app/Http/Requests/Tenant/Panel/Store/SaveFooterRequest.php`
- Add validation rules for `footer_logo_upload_ar`, `footer_logo_upload_en`, `footer_logo_width`.

### 5. `app/Http/Controllers/Tenant/Panel/Store/AppearanceController.php`
- `saveFooter()`: handle `footer_logo_upload_ar` / `footer_logo_upload_en` file uploads (store under `appearances/logos/`), keep existing paths if no new upload, then call a new service method.
- `index()`: add `footer_logo_path_ar`, `footer_logo_path_en`, `footer_logo_width` to the view data (new `footerLogo` key).
- `validateFooter()`: already delegates to request — no change needed.

### 6. `app/Services/Tenant/TenantPanelService.php`
- Add `saveFooterLogoSettings(array $data): void` — same pattern as `saveAppearanceSettings()` but for the three footer logo keys, with old-file cleanup.
- Call it from the controller's `saveFooter()`.

### 7. `resources/views/tenant/pages/store/appearance/index.blade.php` — Footer tab
- Add `files="true"` to the footer form.
- Add a new **Footer Logo** card section (above the existing Footer Text card) with:
  - AR image upload (`footer_logo_upload_ar`) + current preview.
  - EN image upload (`footer_logo_upload_en`) + current preview.
  - Width input (`footer_logo_width`, number, optional) with help text "Recommended width in px when using an image logo (e.g. 160). Leave empty to use theme default."
  - "Leave empty to use the same logo as the header" helper note.
- Pass new `footerLogo` variable from controller.

### 8. `database/seeders/FooterLogoDefaultSeeder.php` *(new file)*
- For every tenant, if `footer_logo_path_ar` / `footer_logo_path_en` are not yet set, copy the current `logo_path_ar` / `logo_path_en` values into them.
- Run with: `php artisan tenants:run db:seed --option="class=FooterLogoDefaultSeeder"`

---

## Execution Order

1. `StorefrontRepository` — add keys + `resolvedFooterLogo()`
2. `storefront-footer-logo.blade.php` component
3. All footer theme partials — swap component tag
4. `SaveFooterRequest` — add rules
5. `TenantPanelService` — add `saveFooterLogoSettings()`
6. `AppearanceController` — update `saveFooter()` + `index()`
7. `appearance/index.blade.php` — Footer tab UI
8. `FooterLogoDefaultSeeder` — backfill existing tenants

---

## Prompts / Messages to Show in UI

- Section title: **"Footer Logo"**
- Section description: **"Override the logo shown in the storefront footer. Leave both images empty to use the same logo as the header."**
- Width field label: **"Logo Width (px)"**
- Width field help: **"Recommended display width for image logos (e.g. 160). Leave empty to use the theme default."**
- AR upload label: **"Arabic Footer Logo"**
- EN upload label: **"English Footer Logo"**

---

## Done Criteria
- [x] Footer tab has a Footer Logo card with AR/EN uploads and width field.
- [x] Saving uploads new images to `appearances/logos/` and stores paths in settings.
- [x] Old unused footer logo images are deleted on replace.
- [x] `<x-storefront-footer-logo>` renders footer-specific image with optional width, falls back to header logo.
- [x] All 10 footer theme partials use the new component.
- [ ] Existing tenants with a header logo see it in the footer by default — **run seeder**: `php artisan tenants:run db:seed --option="class=FooterLogoDefaultSeeder"`
