# Prompt 01 — Foundation (packages, Vite, tenant CSS/JS core, JSON-aware backend)

Read `prompt_00_README_tenant_panel_refactor.md` first. All global invariants apply.
This prompt builds infrastructure only. **No page is converted here.**

## Context (audited)
- Stack: Laravel 13, PHP 8.3, Livewire 4, Stancl Tenancy v3, Tailwind v4 (`@tailwindcss/vite`), Vite 8, axios, SweetAlert2, SortableJS, chart.js, intl-tel-input, laravel-echo + Reverb.
- The tenant layout `resources/views/layouts/tenant.blade.php` loads `resources/css/app.css` + `resources/js/app.js`. These are **shared** with the `app`, `affiliate`, `auth` and `owner` layouts.
- `resources/js/app.js` binds Livewire hooks, a custom "enhanced select", dropzones, Swal toasts, shell/sidebar handlers and dashboard charts.
- `resources/js/bootstrap.js` builds `window.Echo`. Its `authEndpoint` picks `/broadcasting/auth` when the path starts with `/admin`. **The tenant panel is also at `/admin` (on the tenant domain), so it currently authenticates private channels against the non-tenancy endpoint.** The tenant bundle must use `route('tenant.broadcasting.auth')` = `/tenant/broadcasting/auth` explicitly.
- `resources/css/app.css` line 1 imports Google Fonts (Syne, DM Sans) → external request.
- The tenant layout has **no `<meta name="csrf-token">`**.
- `TenantPermission`, `SetupGuard` and `EnforceOnboardingSetup` always return redirects. AJAX callers would receive HTML.
- The theme is applied from `localStorage['nexus-theme']` after `DOMContentLoaded`, which causes a dark→light flash.

---

## 1. Packages

```bash
composer require yajra/laravel-datatables-oracle:"^13.0"
php artisan vendor:publish --tag=datatables   # creates config/datatables.php

npm i jquery@^3.7.1 datatables.net-dt@~2.3.8 datatables.net-responsive-dt@^3.0.8 \
      select2@4.1.0-rc.0 toastr@^2.1.4 flatpickr@^4.6.13 tinymce@^7.9.3 \
      @fontsource/syne @fontsource/dm-sans
```

Version pins (do not "upgrade"):
- **jQuery 3.7** — Select2 4.1 uses APIs removed in jQuery 4.
- **DataTables 2.3 + Responsive 3** — pairs with the Yajra JSON contract.
- **TinyMCE 7** — v8 requires a license key flow.

In `config/datatables.php` set `'error' => env('DATATABLES_ERROR', null)`, so production never leaks SQL in the JSON `error` field.

## 2. Fonts: remove the Google Fonts request

In `resources/css/app.css`, replace line 1 (`@import url("https://fonts.googleapis.com/…Syne…DM+Sans…")`) with:
```css
@import "@fontsource/syne/400.css";
@import "@fontsource/syne/600.css";
@import "@fontsource/syne/700.css";
@import "@fontsource/syne/800.css";
@import "@fontsource/dm-sans/300.css";
@import "@fontsource/dm-sans/400.css";
@import "@fontsource/dm-sans/500.css";
@import "@fontsource/dm-sans/600.css";
```
The font families stay the same, so there is no visual change for any panel. If the Tailwind resolver rejects bare package imports, move these 8 imports into `resources/js/fonts.js`, import that from both `resources/js/app.js` and `resources/js/tenant/app.js`, and leave `app.css` without the Google import.

The 24 logo-builder fonts in the tenant layout `<head>` are handled in prompt 09 (loaded only on the pages that use the logo builder). **Remove that `<link>` block from the layout in prompt 03, not here.**

## 3. Vite

Edit `vite.config.js`:
- Keep every existing input.
- Append a clearly commented **TENANT PANEL** block. Start it with:
  ```js
  // ── TENANT PANEL (/admin on tenant domain) ─────────────────────────────
  'resources/css/tenant/app.css',
  'resources/js/tenant/app.js',
  // page entries — one line per page; added by prompts 03–11, listed explicitly
  ```
- Add `resolve.alias`:
  ```js
  '@tenant': path.resolve(__dirname, 'resources/js/tenant'),
  '@tenant-css': path.resolve(__dirname, 'resources/css/tenant'),
  ```
  Import `path` from `node:path` and derive `__dirname` via `fileURLToPath(import.meta.url)`.
- Add `build.rollupOptions.output.manualChunks(id)` returning:
  - `'vendor-jquery'` for `node_modules/jquery`, `select2`, `datatables.net*`, `toastr`
  - `'vendor-flatpickr'` for flatpickr
  - `'vendor-charts'` for chart.js
  - `'vendor-tinymce'` for tinymce
  - `'vendor-phone'` for intl-tel-input
  - `'vendor-swal'` for sweetalert2
  - `'vendor-sortable'` for sortablejs
  - `undefined` for everything else.
- Set `build.chunkSizeWarningLimit: 900` (TinyMCE).

## 4. Tenant CSS entry

Create `resources/css/tenant/app.css`:
```css
@import "../app.css";                 /* shared tokens + shell + existing classes (unchanged look) */
@import "./base/utilities.css";       /* tenant-only helpers: .is-loading, .is-invalid, .field-error, .sr-only-focusable … */
@import "./components/field.css";
@import "./components/select2.css";
@import "./components/datatable.css";
@import "./components/filters-card.css";
@import "./components/pagination.css";
@import "./components/modal.css";
@import "./components/toast.css";
@import "./components/form.css";
/* prompt 02 creates the remaining component CSS files and appends their imports here */
```
Verify the Tailwind `@source` globs declared inside `app.css` still resolve when that file is imported from here. Build once, then grep `public/build/assets/app-*.css` (the tenant one) for a utility class that is only used in a tenant Blade view.

**Vendor CSS is imported from JS**, in each lazy vendor module (section 6), so it only ships to pages that use the widget. Their theme overrides live in the component CSS files above and use **only existing tokens**: `--bg`, `--surface`, `--elevated`, `--card`, `--card2`, `--border`, `--border2`, `--t1`, `--t2`, `--t3`, `--cyan`, `--violet`, `--green`, `--amber`, `--red`, `--input`, `--shadow`, `--shadow-sm`. That way dark and light themes both work automatically.

`components/toast.css`: restyle toastr so it matches the panel.
- Background `var(--card)`, text `var(--t1)`, 1px `var(--border2)` border, 10px radius, `var(--shadow)`.
- A 3px left accent bar plus the icon tinted `--green` (success), `--red` (error), `--amber` (warning) or `--cyan` (info).
- Progress bar in the accent colour.
- No toastr default green/red slabs.
- RTL: `[dir=rtl]` mirrors the accent bar and uses the `toast-top-left` position.

## 5. Theme without flash (cookie-driven)

- In `bootstrap/app.php`, inside `withMiddleware`, add `$middleware->encryptCookies(except: ['tenant_theme']);`.
- The layout reads it server-side in prompt 03:
  `data-theme="{{ in_array(request()->cookie('tenant_theme'), ['light','dark'], true) ? request()->cookie('tenant_theme') : 'dark' }}"`.
- The JS toggle writes `tenant_theme` (path `/`, 1 year, `SameSite=Lax`). It also keeps writing `localStorage['nexus-theme']` for backward compatibility.

## 6. Tenant JS core

Create this tree. Everything is ES modules; there are no globals except `window.$`/`window.jQuery` (required by plugins) and a read-only `window.Tenant` debug handle.

```
resources/js/tenant/
├── app.js                      entry: import './shell/layout'; import { boot } from './core'; boot();
├── core/
│   ├── index.js                re-exports + boot(): initComponents(document), showFlash(), bindActions(), bindForms(), bindModals()
│   ├── dom.js                  onReady, qs, qsa, delegate(root, event, selector, handler), debounce, throttle, readJson(id), escapeHtml, setBusy(el, bool)
│   ├── events.js               on(name, fn) / off / emit(name, detail) on a private EventTarget; names below
│   ├── http.js                 axios instance + interceptors (below)
│   ├── toast.js                lazy toastr wrapper + flash bridge
│   ├── confirm.js              SweetAlert2 themed confirm()
│   ├── forms.js                TenantForm class + bindForms()
│   ├── actions.js              declarative action buttons
│   ├── modals.js               x-tenant::modal controller
│   ├── registry.js             registerComponent(selector, loader) + initComponents(root)
│   └── echo.js                 getEcho() lazily builds Echo with the tenant auth endpoint
├── shell/layout.js             sidebar / theme / date / nav groups (ported from app.js)
├── vendor/
│   ├── jquery.js               import $ from 'jquery'; window.$ = window.jQuery = $; export default $;
│   ├── select2.js              async: jquery → select2 factory → css; returns $
│   ├── datatables.js           async: jquery → datatables.net-dt + responsive → css; returns DataTable
│   ├── flatpickr.js
│   └── tinymce.js              (prompt 02)
├── components/                 (prompt 02)
├── modules/                    (prompt 07: payment-sdk-loader, payment-gateway)
└── pages/                      (prompts 03–11)
```

### 6.1 `core/http.js`
- `const http = axios.create({ headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, withCredentials: true })`.
- A request interceptor adds `X-CSRF-TOKEN` from `<meta name="csrf-token">`.
- Export `request(method, url, data, { toast = true, silent = false, signal } = {})`, plus the shorthands `get`, `post`, `put`, `patch`, `del`, `upload` (FormData, with progress callback).
  - For `put`/`patch`/`del` with FormData, send POST with `_method` appended (Laravel method spoofing, needed for multipart).
- **Success interceptor:** if the method is mutating and `toast !== false` and `data.message` exists → `toast.success(data.message)`. If `data.redirect` exists → `sessionStorage['tenant:flash'] = JSON.stringify({ message, type: 'success' })`, then `location.assign(redirect)`. Resolve to `response.data`.
- **Error interceptor.** Map the status to a toast unless `silent`:
  - 401 → `location.assign(<login url from meta tenant-login-url>)`
  - 403 → `data.message || 'You do not have permission to perform this action.'`; if `data.redirect`, navigate after the toast.
  - 404 → `'The requested record no longer exists.'`
  - 409 → `data.message` (setup/plan guard); if `data.redirect`, navigate.
  - 419 → `'Your session expired. Reloading…'`, then `location.reload()` after 1.2s.
  - 422 → **no generic toast here**; the form engine handles it. For non-form actions, toast `data.message` using `data.toast_type` (`error` default, `warning` supported).
  - Any error body carrying `toast_type` uses that toast type instead of `error`.
  - 429 → `'Too many requests. Please wait a moment.'`
  - ≥500 or network → `'Something went wrong. Please try again.'`
  - Reject with a normalised `{ status, message, errors, data }`.
- Never toast for GET requests (DataTables, lookups) unless the caller passes `{ toast: true }`.

### 6.2 `core/toast.js`
- `toast.success|error|warning|info(message, title?)`. Internally `await import('toastr')` (after `vendor/jquery.js`), configured once:
  ```js
  closeButton: true, progressBar: true, newestOnTop: true, preventDuplicates: true,
  timeOut: 4000, extendedTimeOut: 1500, escapeHtml: true,
  positionClass: document.dir === 'rtl' ? 'toast-top-left' : 'toast-top-right'
  ```
- `showFlash()` shows `#flash-status[data-message][data-type]` (already rendered by the layout from `session('status')`/`status_type`), then `sessionStorage['tenant:flash']`. Clear both after showing.
- Also support `session('setup_warning')` / `session('setup_error')`: the layout renders them as additional `#flash-*` elements in prompt 03.

### 6.3 `core/confirm.js`
- `confirm({ title='Are you sure?', text='This action cannot be undone.', confirmText='Confirm', cancelText='Cancel', icon='warning', danger=false })` → `Promise<boolean>`.
- Uses `sweetalert2` themed from CSS vars (`background: var(--card)`, `color: var(--t1)`; confirm button `--red` when `danger`, otherwise `--cyan`). This is the same visual language as the existing `admin-confirm`.

### 6.4 `core/forms.js` — the validate + submit engine
Markup contract (produced by `x-tenant::form`, prompt 02):
```html
<form data-tenant-form action="…" method="POST" data-method="PUT"
      data-validate-url="…/validate"
      data-success="reload-table:#coupons-table close-modal reset"   <!-- space-separated behaviours -->
      data-confirm="Optional confirm text"
      novalidate enctype="multipart/form-data">
```
The `TenantForm` class:
- Keeps a `touched` Set of field keys. On `focusout` and `change` (debounced 350 ms per field), it marks the field touched and calls `validate({ only: touched })`.
- **`validate()`:**
  - Aborts the previous in-flight validation (AbortController).
  - POSTs `FormData` to `data-validate-url`, **excluding `File` values**. File fields are validated on submit only.
  - Then renders errors **only for keys in `touched`**, and clears errors for touched keys that now pass.
  - Errors are matched to fields by exact dot key, then by wildcard ancestor (`variants.2.price` → `variants.2` → `variants`), then shown in the form summary `[data-form-errors]`.
- **`submit()`:**
  - Runs `data-confirm` via `confirm()` if present.
  - Syncs editors (`tinymce.triggerSave()`), phone inputs and select2 (their components expose `beforeSubmit` hooks via the registry).
  - Builds `FormData` (unchecked checkboxes already have hidden `0` inputs from the components) and sends it with `data-method`.
  - While in flight it disables the submit buttons and adds `.is-loading`. On 422 it marks **all** error keys as touched, renders them, toasts `data.message || 'Please fix the highlighted fields.'`, focuses and scrolls to the first invalid field, and activates the enclosing tab if the field is inside a hidden tab panel (it emits `tenant:reveal`, and the tabs/locale-tabs components listen).
  - On success it applies the `data-success` behaviours:
    - `reload-table:<selector>`
    - `close-modal`
    - `reset`
    - `redirect` — uses the JSON `redirect`
    - `reload-page`
    - `emit:<event>`
    - `fill` — fills the form with `response.data`
    - `none`
  - It emits `tenant:form:success` / `tenant:form:error` on the form element with `detail`.
- API: `TenantForm.for(formEl)` (memoised), `.fill(object)` (handles dot keys, checkbox groups, radios, multi-selects, select2 including appending missing `<option>`s for AJAX selects when `data._options` is provided, editors, colour inputs, switches), `.reset()` (restores initial values and clears errors), `.setErrors(errors)`, `.clearErrors()`.
- Error DOM contract: each field wrapper has `data-field="dot.key"`, and gets `.is-invalid` on error. The message goes into its `[data-error-for="dot.key"]`. Messages are inserted with `textContent` (never HTML).

### 6.5 `core/actions.js` — declarative one-click actions
Markup:
```html
<button data-action-url="…" data-action-method="DELETE"
        data-confirm="Delete this coupon?" data-confirm-danger
        data-payload='{"active":1}'
        data-success="reload-table:#coupons-table">Delete</button>
```
One delegated click listener on `document`. It reads the attributes, confirms if needed, sends through `http`, sets a busy state on the button, and applies the same `data-success` behaviours as forms. Row toggles (`x-tenant::switch` with `data-action-url`) use the same path on `change`, and revert the checkbox if the request fails.

### 6.6 `core/modals.js`
- `openModal(id, { url?, fill?, mode? })`, `closeModal(id)`, and delegation for `[data-modal-open="id"]` / `[data-modal-close]`. Also ESC, backdrop click (unless `data-static`), body scroll lock, focus trap (first focusable, wrap Tab), and return focus to the opener.
- **Remote fill:** when opened with `data-modal-fill-url` (an edit button), it GETs the JSON, calls `TenantForm.for(modal.querySelector('form')).fill(data.data)`, and sets the form `action`/`data-method`/title from `data-modal-*` attributes on the opener.
- On close it resets the contained form unless `data-keep`.

### 6.7 `core/registry.js`
`registerComponent('[data-tenant-select2]', () => import('../components/select2.js'))` — each component module exports `init(el)` and optionally `destroy(el)` / `beforeSubmit(el)`. `initComponents(root)` finds the matching elements not yet initialised (`data-tenant-ready`), imports their loaders **lazily** (so a page without tables never downloads DataTables), and calls `init`. Call it after any HTML injection (modal content, DataTables `draw` for row-level switches/tooltips).

### 6.8 `core/echo.js`
`getEcho()` dynamically imports `laravel-echo` and `pusher-js` and builds one instance with the same Reverb env as `resources/js/bootstrap.js`, but with `authEndpoint: document.querySelector('meta[name="tenant-broadcast-auth"]').content`. **Do not import `resources/js/bootstrap.js` in the tenant bundle.**

### 6.9 `shell/layout.js`
Port these functions from `resources/js/app.js` **verbatim in behaviour**: `setDateLabel`, `applyTheme`, `toggleTheme`, `openMobileSidebar`, `closeMobileSidebar`, `toggleDesktopSidebar`, `handleHamburger`, `syncSidebarLayout`, `setActiveNav`, `setActiveSubNav`, `toggleGroup`. Also port the delegated `[data-action]` click handler (`close-mobile`, `handle-ham`, `toggle-theme`, `set-active`, `set-sub-active`, `toggle-group`), the resize handler, and ESC to close the sidebar.
- `toggleTheme` also writes the `tenant_theme` cookie and emits `tenant:theme-changed`. Charts listen to rebuild.
- Persist the desktop collapsed state in `localStorage['tenant-sb-collapsed']`.
- Do not port the Livewire hooks, the enhanced-select system or the charts. Charts go to the dashboard page entry in prompt 04.

### 6.10 Event names (`core/events.js`)
`tenant:table:reload` (detail `{ selector }`), `tenant:form:success`, `tenant:form:error`, `tenant:reveal`, `tenant:theme-changed`, `tenant:modal:opened`, `tenant:modal:closed`, `tenant:setup-progress:refresh` (replaces Livewire `setup-progress-refresh`).

## 7. Backend base layer

### 7.1 `app/Http/Controllers/Tenant/Panel/PanelController.php`
`abstract class PanelController extends Controller`, with protected helpers:
```php
protected function success(string $message, array $data = [], ?string $redirect = null, int $status = 200): JsonResponse
// If $redirect is set, also session()->flash('status', $message) + flash('status_type','success').
// JSON body: ['success'=>true,'message'=>…,'data'=>…,'redirect'=>…]

protected function failure(string $message, int $status = 422, array $errors = [], ?string $redirect = null, string $toastType = 'error'): JsonResponse

protected function validFormResponse(): JsonResponse                  // ['valid' => true]

protected function filters(Request $request, array $allowed): array
// Returns array_intersect_key((array) $request->input('filters', []), array_flip($allowed)) with trimmed strings.

protected function streamCsv(string $fileName, array $headers, iterable $rows): StreamedResponse
// UTF-8 BOM, fputcsv, lazy iteration, Content-Type text/csv; charset=UTF-8, no-store.
```

### 7.2 `app/Http/Requests/Tenant/Panel/TenantFormRequest.php`
- `abstract class TenantFormRequest extends FormRequest`. `authorize(): bool { return true; }`.
- A helper `protected function booleanFields(): array` that is merged in `prepareForValidation()` via `$this->boolean()` for the listed keys.
- Keep Laravel's default JSON 422 (`{message, errors}`).

### 7.3 `app/Exceptions/Tenant/PanelActionException.php`
- `final class PanelActionException extends \RuntimeException implements Responsable` (or register a renderer in `bootstrap/app.php`).
- Constructor `(string $message, int $status = 422, ?string $redirect = null, string $toastType = 'error')`. `$toastType` is `error` or `warning`; several Livewire actions used `warning` toasts (e.g. "You cannot delete the currently signed-in tenant admin."). It renders `['success'=>false,'message'=>…,'redirect'=>…,'toast_type'=>…]` when `expectsJson()`, and otherwise `back()->with('status', $message)->with('status_type', $toastType)`.
- Controllers throw it for business-rule failures, e.g. `if (! $limits->canPerform('categories')) throw new PanelActionException($limits->errorMessage('categories'));`.

### 7.4 JSON-aware middleware
Add an `if ($request->expectsJson())` branch **before** each existing redirect. Keep the HTML behaviour unchanged.
- `TenantPermission`: unauthenticated → `401 {message:'Unauthenticated.', redirect: route('tenant.login')}`. Missing permission → `403 {message:'You do not have permission to access that section.'}`.
- `SetupGuard`: `409 {message: $message, redirect: route('tenant.onboarding', ['tab'=>'setup','item'=>$definition['tab']])}`.
- `EnforceOnboardingSetup`: `409 {message:'Please complete your store setup before accessing other pages.', redirect: route('tenant.onboarding',['tab'=>'setup'])}`.

### 7.5 Exception rendering (`bootstrap/app.php` → `withExceptions`)
Add these before the existing renderers; they apply only when `$request->is('admin*') && $request->expectsJson()` on a tenant domain:
- `ModelNotFoundException` / 404 `HttpException` → `404 {message:'The requested record no longer exists.'}`
- `TokenMismatchException` → `419 {message:'Your session expired. Please reload the page.'}`
- `AuthorizationException` → `403`

The existing `HttpException` renderer already returns `null` for `admin*`. Keep it.

### 7.6 Rate limiting
In `AppServiceProvider::boot()`: `RateLimiter::for('tenant-validate', fn (Request $r) => Limit::perMinute(180)->by($r->user('tenant')?->id ?: $r->ip()));`. Every `*.validate` route gets `->middleware('throttle:tenant-validate')`.

## 8. Route file split
- Create `routes/tenant_panel.php`. Move into it the **entire** body of `Route::prefix('admin')->group(function () { … })` from `routes/tenant.php`: the redirect `/`, `guest:tenant` login, logout, impersonate, email verify, and the whole `['auth:tenant','tenant.setup.enforce']` group. Move the `use` statements that are only used there as well.
- In `routes/tenant.php`, replace that body with `Route::prefix('admin')->group(base_path('routes/tenant_panel.php'));`, still inside the outer `['web', InitializeTenancyByDomain, PreventAccessFromCentralDomains]` group.
- The storefront routes stay in `tenant.php`, untouched.
- Run `php artisan route:list --path=admin > /tmp/before.txt` **before** the split and `> /tmp/after.txt` after. `diff` must show no differences.

## 9. Layout prerequisites (minimal; the full shell comes in prompt 03)
In `resources/views/layouts/tenant.blade.php` `<head>`, add:
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="tenant-broadcast-auth" content="{{ route('tenant.broadcasting.auth') }}">
<meta name="tenant-login-url" content="{{ route('tenant.login') }}">
```

## 10. Verification
1. `composer show yajra/laravel-datatables-oracle` shows a 13.x version.
2. `npm run build` passes. `public/build/manifest.json` contains `resources/css/tenant/app.css` and `resources/js/tenant/app.js`, plus the `vendor-*` chunks.
3. `grep -rn "fonts.googleapis" resources/css` returns nothing.
4. Route list diff before and after the split is empty.
5. `curl -H 'Accept: application/json' https://<tenant>/admin/orders` without a session → 401 JSON. With a session but missing permission → 403 JSON. Without `Accept` → the old redirect behaviour.
6. Central admin, affiliate and owner panels look and behave exactly as before (the `app.css`/`app.js` behaviour is untouched apart from the fonts).
