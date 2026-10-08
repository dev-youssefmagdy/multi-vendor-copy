# Blade Theme Variants Lifecycle — Planning Document

> Author: Senior Fullstack / Business Analyst review  
> Date: 2026-10-08  
> Status: **Ready for implementation**

---

## Problem Statement

Right now uploading multiple blade theme ZIPs via `/admin/store/blade-theme` creates multiple `BladeTheme` rows in the central DB, but the Themes page (`/admin/store/themes`) always shows **exactly one card** under the "custom" theme — because there is only a single `HomeVariant` row seeded at migration time (`theme_slug=custom, key=default`).

The desired behaviour: every uploaded (and approved) blade theme ZIP appears as its own **variant card** under the "custom" theme on the Themes page, switchable exactly like variants on any other theme, each with its own folder and page-builder sections.

---

## Correct Lifecycle

### Phase 1 — Upload

**Trigger:** Vendor POSTs a ZIP to `POST /admin/store/blade-theme`

**Current behaviour:**
1. ZIP validated and extracted to `storage/app/tenant-blade-themes/{tenantId}/{version}/`
2. `BladeTheme` record created (`status=pending, is_active=false`)

**Required addition (step 3):**
3. Create a `HomeVariant` row in the **central DB**:
   - `theme_slug = 'custom'`
   - `key = {version}` (e.g. `20261008143022-AbCdEf`) — unique, maps back to the `BladeTheme`
   - `name = {original_filename}` (strip `.zip`)
   - `description = 'Uploaded blade theme'`
   - `is_active = false`
   - `is_default = false`
   - `sections = null` (discovered later at activation time)
   - `blade_theme_id = {BladeTheme.id}` — **new FK column needed** (see migrations below)

**Files to change:**
- `app/Services/Tenant/BladeThemeService.php` → `upload()` method
- `app/Models/HomeVariant.php` → add `blade_theme_id` to `$fillable`
- **New migration:** `add_blade_theme_id_to_home_variants_table`

---

### Phase 2 — Admin Approval

**Trigger:** Central admin approves the `BladeTheme` record

**Current behaviour:** `BladeThemeService::approve()` sets `status=approved`, re-symlinks if already active.

**No change needed.** The `HomeVariant` row is already created at upload time. The variant card on the Themes page will become actionable (activatable) once the `BladeTheme.status` is `approved`.

**Required:** The Themes page variant cards for `custom` must check `BladeTheme.status == approved` before offering an "Activate" button. Show a "Pending approval" badge otherwise.

**Files to change:**
- `app/Http/Controllers/Tenant/Panel/Store/ThemesController.php` → `index()` variant card building
- Blade view for themes variant card (add status badge)

---

### Phase 3 — Themes Page Renders Variants

**Trigger:** Vendor visits `/admin/store/themes`

**Current behaviour:** `ThemesController::index()` fetches `HomeVariant` rows from central DB for the tenant's theme slugs. Since only one `HomeVariant` exists for `custom`, only one card shows.

**Required change:** After upload (Phase 1), multiple `HomeVariant` rows exist for `theme_slug=custom`. The controller already fetches them generically — **the only problem is they don't exist yet**. Once Phase 1 creates them, this page automatically shows multiple cards.

**Additional requirements per card:**
- Show `BladeTheme.original_filename` as card title
- Show `BladeTheme.status` as badge (`Pending`, `Approved`, `Rejected`)
- Disable activate button unless `status == approved`
- Show rejection reason if `status == rejected`

**Files to change:**
- `app/Http/Controllers/Tenant/Panel/Store/ThemesController.php` → pass `blade_theme_id` + status to `$variantCards`
- Blade/Livewire view for the themes index page

---

### Phase 4 — Activate a Variant

**Trigger:** Vendor clicks "Activate" on a variant card for the custom theme

**Route:** `POST /themes/{theme}/variants/{variant}/activate` → `ThemesController@activateVariant`

**Current behaviour:** `activateVariant()` upserts a `TenantHomeVariant` row and calls `TenantPanelService::activateTheme()` which calls `BladeThemeService::activateLatestApprovedForCurrentTenant()` — always picks the **latest approved**, ignoring which variant the vendor chose.

**Required changes:**

1. `ThemesController::activateVariant()` must detect `theme->slug == 'custom'` and call a new method:
   ```
   BladeThemeService::activateSpecificVariant(tenantId, homeVariantId)
   ```

2. `BladeThemeService::activateSpecificVariant()`:
   - Looks up `HomeVariant` by ID, reads `blade_theme_id`
   - Looks up `BladeTheme` — asserts it belongs to this tenant and is `approved`
   - Marks all other `BladeTheme` rows for this tenant as `is_active = false`
   - Marks this one `is_active = true`
   - Calls `relinkLiveViews()` passing the **specific** storage path (not the latest)
   - Upserts `TenantHomeVariant` for `country_id = null` pointing to this `HomeVariant`
   - Calls `syncThemeRow(activate: true)` to mark the `custom` tenant Theme row as active
   - Calls `seedPageBuilderSectionsForVariant()` (see Phase 5)

3. `relinkLiveViews()` must be refactored to accept an **explicit `BladeTheme` instance**, not always re-query the "latest active". The current implicit re-query loses the intent.

**Files to change:**
- `app/Http/Controllers/Tenant/Panel/Store/ThemesController.php` → `activateVariant()`
- `app/Services/Tenant/BladeThemeService.php` → new `activateSpecificVariant()`, refactor `relinkLiveViews()`
- `app/Services/Tenant/TenantPanelService.php` → `activateTheme()` must pass the chosen `HomeVariant` ID through

---

### Phase 5 — Page Builder Sections Seeded per Variant

**Trigger:** Immediately after activating a variant (called from Phase 4)

**Current behaviour:** `discoveredHomeSectionKeys()` always reads from the **active** symlink / latest active `BladeTheme` storage path. No per-variant seeding exists.

**Required new method:** `BladeThemeService::seedPageBuilderSectionsForVariant(tenantId, bladeThemeId, homeVariantId)`

Logic:
1. Find the `BladeTheme` by ID → get `storage_path`
2. Scan `{storage_path}/pages/home/sections/*.blade.php` → collect section keys
3. Find the tenant's `custom` Theme row → get `theme_id`
4. For each discovered section key, upsert a `TenantPageSection`:
   ```
   theme_id       = {custom theme id}
   home_variant_id = {homeVariantId}
   page           = 'home'
   section_key    = {key}
   sort_order     = {index}
   is_visible     = true
   ```
   (Use `updateOrCreate` — idempotent, safe to re-run on re-activation)

**Why at activation time (not upload)?** The tenant DB is only available when running in tenant context. Central admin approval runs in central context. Seeding at activation is safe and idempotent.

**Files to change:**
- `app/Services/Tenant/BladeThemeService.php` → new `seedPageBuilderSectionsForVariant()`
- Called from `activateSpecificVariant()` (same service)

---

### Phase 6 — Delete a Blade Theme Upload

**Trigger:** Vendor deletes a non-active upload from the blade-theme page

**Current behaviour:** `BladeThemeController::destroy()` deletes the `BladeTheme` record and removes the extracted folder. Only non-active themes can be deleted.

**Required addition:**
- Also delete the corresponding `HomeVariant` row (matched via `blade_theme_id`)
- Also delete `TenantPageSection` rows for `(theme_id, home_variant_id)` in the **tenant** DB

**Files to change:**
- `app/Http/Controllers/Tenant/Panel/Store/BladeThemeController.php` → `destroy()`
- Or delegate to a new `BladeThemeService::delete()` method

---

## Data Model Changes

### Central DB — `home_variants` table

**New migration:** `add_blade_theme_id_to_home_variants_table`

```sql
ALTER TABLE home_variants
  ADD COLUMN blade_theme_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_home_variants_blade_theme
    FOREIGN KEY (blade_theme_id) REFERENCES blade_themes(id) ON DELETE SET NULL;
```

> `ON DELETE SET NULL` → if a blade theme is hard-deleted from the DB, the variant row becomes orphaned but doesn't cascade-delete (safe fallback).

---

## Files Inventory — What to Change

| File | Change |
|------|--------|
| `database/migrations/xxxx_add_blade_theme_id_to_home_variants_table.php` | New migration — adds `blade_theme_id` FK |
| `app/Models/HomeVariant.php` | Add `blade_theme_id` to `$fillable`; add `bladeTheme()` belongsTo |
| `app/Services/Tenant/BladeThemeService.php` | `upload()`: create `HomeVariant`; new `activateSpecificVariant()`; new `seedPageBuilderSectionsForVariant()`; refactor `relinkLiveViews()` to accept explicit `BladeTheme` |
| `app/Http/Controllers/Tenant/Panel/Store/ThemesController.php` | `index()`: pass status/blade_theme_id into cards; `activateVariant()`: route custom theme to new service method |
| `app/Http/Controllers/Tenant/Panel/Store/BladeThemeController.php` | `destroy()`: delete `HomeVariant` + tenant page sections |
| `app/Services/Tenant/TenantPanelService.php` | `activateTheme()`: thread chosen `HomeVariant` ID to blade service |
| Blade views for themes index | Status badge on custom variant cards; disable button when pending/rejected |

---

## Implementation Prompts (in order)

### Prompt 1 — Migration: add `blade_theme_id` to `home_variants`

```
Create a new Laravel migration file named:
  database/migrations/{timestamp}_add_blade_theme_id_to_home_variants_table.php

Add a nullable unsigned big integer column `blade_theme_id` to the `home_variants`
table. Add a foreign key referencing `blade_themes(id)` with ON DELETE SET NULL.
Add an index on `blade_theme_id`.
```

---

### Prompt 2 — Model: `HomeVariant` update

```
In app/Models/HomeVariant.php:
1. Add 'blade_theme_id' to $fillable.
2. Add a bladeTheme() BelongsTo relationship to App\Models\BladeTheme.
```

---

### Prompt 3 — Service: `upload()` creates `HomeVariant`

```
In app/Services/Tenant/BladeThemeService.php, inside upload(), after the
BladeTheme::create() call:

Create a HomeVariant row (central DB) inside tenancy()->central():
  theme_slug       = 'custom'
  key              = $version
  name             = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
  description      = 'Uploaded blade theme'
  is_active        = true
  is_default       = false
  sections         = null
  blade_theme_id   = $bladeTheme->id

Return the BladeTheme as before.
```

---

### Prompt 4 — Service: `activateSpecificVariant()` + refactor `relinkLiveViews()`

```
In app/Services/Tenant/BladeThemeService.php:

1. Add method activateSpecificVariant(string $tenantId, int $homeVariantId): void
   - Load the HomeVariant (central). Assert blade_theme_id is set.
   - Load the BladeTheme by blade_theme_id. Assert tenant_id matches + status == approved.
   - In central context: set all this tenant's blade_themes.is_active = false, then
     set this one is_active = true.
   - Call relinkLiveViews($tenantId, $bladeTheme) — pass explicit instance.
   - Call seedPageBuilderSectionsForVariant($tenantId, $bladeTheme, $homeVariantId).
   - Call syncThemeRow(activate: true) to mark custom Theme row as active.

2. Refactor relinkLiveViews(string $tenantId, ?BladeTheme $theme = null): void
   - If $theme is null, fall back to activeBladeTheme() query (keeps backward compat).
   - Remove the activeBladeTheme() query when $theme is explicitly passed.

3. Add method seedPageBuilderSectionsForVariant(
       string $tenantId, BladeTheme $bladeTheme, int $homeVariantId
   ): void
   - Build sections dir path: storage_path('app/' . $bladeTheme->storage_path) . '/pages/home/sections'
   - Glob *.blade.php files → collect keys (basename, strip .blade.php), sort.
   - Find the custom Theme row in tenant DB: Theme::where('slug','custom')->first().
   - For each key with index $i, updateOrCreate TenantPageSection:
       match:  [theme_id, home_variant_id, page='home', section_key]
       values: [sort_order=$i, is_visible=true]
```

---

### Prompt 5 — Controller: `ThemesController::activateVariant()` custom routing

```
In app/Http/Controllers/Tenant/Panel/Store/ThemesController.php,
in the activateVariant(Theme $theme, int $variant) method:

After resolving the HomeVariant by $variant ID, add this block before
the generic TenantPanelService call:

  if ($theme->slug === 'custom') {
      app(BladeThemeService::class)->activateSpecificVariant(
          tenant()->getTenantKey(),
          $homeVariant->id,
      );
      return back()->with('success', 'Blade theme variant activated.');
  }

Keep the existing non-custom flow unchanged below.
```

---

### Prompt 6 — Controller: `ThemesController::index()` — status on cards

```
In ThemesController::index(), when building $variantCards for theme_slug='custom',
for each HomeVariant:

1. Load the related BladeTheme via $homeVariant->blade_theme_id.
2. Add these fields to the card array:
   - 'blade_status' => $bladeTheme?->status  (null for legacy 'default' variant)
   - 'blade_filename' => $bladeTheme?->original_filename
   - 'can_activate' => $bladeTheme?->status === BladeTheme::STATUS_APPROVED
3. Set is_active on the card only when blade_status is approved AND
   TenantHomeVariant points to this variant.

Pass these to the view so the variant card can show a status badge and
disable the Activate button when status is pending or rejected.
```

---

### Prompt 7 — Controller: `BladeThemeController::destroy()` cleanup

```
In app/Http/Controllers/Tenant/Panel/Store/BladeThemeController.php,
in the destroy(int $upload) method, after deleting the BladeTheme record:

Inside a tenancy()->central() block:
1. Delete the HomeVariant row where blade_theme_id = $upload (the original ID,
   captured before deletion).

In the tenant context (outside central block):
2. Find the custom Theme row: Theme::where('slug','custom')->first().
3. If found, delete TenantPageSection where theme_id = $theme->id
   AND home_variant_id IN (the deleted HomeVariant IDs — pass as array).
```

---

### Prompt 8 — Blade view: status badge on custom variant cards

```
In the themes index Blade/Livewire view, for each variant card under the 'custom' theme:

1. If $card['blade_status'] === 'pending': show a yellow badge "Pending Approval"
   and disable the Activate button.
2. If $card['blade_status'] === 'rejected': show a red badge "Rejected"
   and disable the Activate button. Optionally show rejection reason as tooltip.
3. If $card['blade_status'] === 'approved' or null (legacy): show normal card.
4. Use $card['blade_filename'] as the card's subtitle when set.
```

---

## Edge Cases & Guardrails

| Case | Handling |
|------|----------|
| Vendor uploads a theme but admin hasn't approved it yet | Card shows on Themes page with "Pending Approval" badge. Activate button disabled. |
| Vendor deletes an active blade theme | `destroy()` blocks deletion when `is_active = true` (already enforced). |
| Vendor switches from custom theme to another theme | `TenantPanelService` calls `deactivateAllForCurrentTenant()` — symlink removed, all `is_active = false`. Variant choice is remembered via `TenantHomeVariant` row. |
| Two variants have the same filename | Key is based on `version` (timestamp + random), not filename — no collision. |
| Legacy `default` HomeVariant (seeded in migration) | Has `blade_theme_id = null`. Still shown on Themes page. `can_activate` should be false unless at least one `BladeTheme` is approved — handle gracefully. Consider hiding the seeded `default` variant if no blade theme is linked. |
| Page builder sections for a variant that hasn't been activated yet | Sections are only seeded at activation time. Before activation, the Page Builder has no rows for that `home_variant_id` — this is correct; don't show the builder until the variant is active. |
| Tenant re-activates a previously active variant | `updateOrCreate` in `seedPageBuilderSectionsForVariant()` is idempotent — safe. |

---

## Summary

The fix is a **data-model bridge**: each `BladeTheme` upload must create a corresponding `HomeVariant` row (linked via `blade_theme_id` FK). The Themes page already has the plumbing to show multiple variant cards per theme — it just needs the rows to exist. Activation of a specific variant routes to the blade service which re-symlinks to the correct version folder and seeds page-builder sections from that version's `pages/home/sections/` directory.

Total new code: ~150 lines across service + controllers. Total new migrations: 1.
