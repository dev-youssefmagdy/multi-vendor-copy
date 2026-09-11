# Prompt 03 — Livewire-free Tenant Layout, Shell Widgets, Login/Logout

Requires prompts 01–02. All invariants in `prompt_00_README_tenant_panel_refactor.md` apply.
**No redesign.** The shell must look pixel-identical to today. It only changes technology.

## Context (audited)
- `resources/views/layouts/tenant.blade.php`:
  - `<head>` loads **24 Google Fonts** (logo builder), `app.css`/`app.js` and `@livewireStyles`.
  - The body includes `layouts.tenant.sidebar` and `layouts.tenant.header`, then `@livewire('tenant.onboarding.onboarding-tour')`, `{{ $slot }}` + `@yield('content')`, `@livewireScripts` and `@stack('scripts')`.
  - It renders `#flash-status` from `session('status')`.
- `layouts/tenant/sidebar.blade.php`:
  - queries `TenantNavigation::visibleSections()` and the support unread count (central DB via `tenancy()->central(...)`)
  - embeds `@livewire('tenant.widgets.account-setup-progress')`
  - uses a DiceBear avatar and brand text "NEXUS / VENDOR PANEL".
- `layouts/tenant/header.blade.php`:
  - does inline `@php` queries for route info, the active subscription and the package (plan pill)
  - includes `<x-tenant-notification-bell />` (global component using **Alpine** + inline `<script>` + Echo private `tenant.{id}.notifications`, event `.notification.created`, window event `notification-read`)
  - has the theme toggle, a DiceBear avatar and a logout `<form>`.
- `App\Livewire\Tenant\Widgets\AccountSetupProgress`:
  - `wire:poll.15s="refresh"`
  - `toggle()` (expanded state)
  - `markPagesReviewed()` → `TenantPanelService::markDefaultPagesReviewed()`
  - listens to `setup-progress-refresh`
  - data from `TenantNavigation::setupProgress()`
  - view `livewire/tenant/widgets/account-setup-progress.blade.php` (has an inline `<style>`).
- `App\Livewire\Tenant\Onboarding\OnboardingTour` (persistent in the layout):
  - `mount()`:
    - shows the **compliance modal** if compliance pages exist and the current version isn't accepted
    - otherwise **redirects to `tenant.onboarding` tab `tour` when `tour_seen_at` is null** (except on the onboarding route)
    - otherwise computes `showSetupBanner`
  - `acceptCompliance()` requires the checkbox, saves `TenantModel::saveData(tenant('id'), ['compliance_accepted_at'=>now(), 'compliance_version'=>$version])`, then re-runs the redirect check.
  - Helpers: `compliancePages()`, `compliancePagesExist()`, `complianceVersion()`, `hasAcceptedCurrentCompliance()`.
  - View `livewire/tenant/onboarding/tour.blade.php` (inline `<style>`).
- `App\Livewire\Tenant\Auth\LoginPage`:
  - `mount()` redirects authenticated users to the dashboard.
  - `login()` validates (read the rules in the class), looks up `AdminUser` by lowercased email, and returns field error `email` for:
    - bad credentials → "The provided tenant admin credentials are invalid for this domain."
    - tenant status not Active/Onboarding → "This tenant account is not allowed to access the vendor panel."
    - admin inactive → "This tenant admin account is inactive."
  - Then `Auth::guard('tenant')->login($admin, $remember)`, `session()->regenerate()`, `redirectIntended(route('tenant.dashboard'))`.
  - Renders the **shared** `livewire.auth.panel-login` in `layouts.auth`. **Do not modify either.**
- Logout: a closure route `POST /admin/logout` (`tenant.logout`).

## 1. View composer (remove queries from Blade)
Create `app/View/Composers/Tenant/ShellComposer.php`, registered in `AppServiceProvider` for `tenant.layouts.partials.*`. It provides a single `$shell` array:
- `sections` → `TenantNavigation::visibleSections()`
- `currentRoute`, `routeInfo` → `TenantNavigation::routeInfo(...)`
- `supportUnread` → the same `tenancy()->central(...)` count as today
- `onboardingProgress` → `TenantNavigation::onboardingSetupProgress()`
- `user` (tenant admin), `role` (name or 'Tenant Admin')
- `plan` → `['name','expiry','expired']`, using the exact same Subscription/Package logic as `header.blade.php`
- `unreadNotifications` → `TenantNotification::unread()->count()`
- `setupProgress` → `TenantNavigation::setupProgress()`
- `tenantName`, `tenantId`

Memoise per request (one instance). The partials only read `$shell`.

## 2. New layout `resources/views/tenant/layouts/app.blade.php`
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" dir="{{ $shellDir ?? 'ltr' }}"
      data-theme="{{ in_array(request()->cookie('tenant_theme'), ['light','dark'], true) ? request()->cookie('tenant_theme') : 'dark' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="tenant-broadcast-auth" content="{{ route('tenant.broadcasting.auth') }}">
  <meta name="tenant-login-url" content="{{ route('tenant.login') }}">
  <meta name="tenant-id" content="{{ tenant('id') }}">
  <title>@yield('title', 'Dashboard') · {{ tenant('name') ? tenant('name').' Vendor Panel' : 'Vendor Panel' }}</title>
  @vite(['resources/css/tenant/app.css', 'resources/js/tenant/app.js'])
  @stack('tenant-vite')
</head>
<body class="t-scope">
  @include('tenant.layouts.partials.flash')      {{-- #flash-status + setup_warning / setup_error + validation fallback --}}
  <div id="ov" data-action="close-mobile"></div>
  @include('tenant.layouts.partials.sidebar')
  @include('tenant.layouts.partials.header')
  @include('tenant.layouts.partials.compliance-modal')   {{-- replaces OnboardingTour compliance --}}
  @include('tenant.layouts.partials.setup-banner')       {{-- replaces OnboardingTour showSetupBanner --}}
  <main id="mn">@yield('content')</main>
</body>
</html>
```
- Remove the 24-font Google `<link>`, `@livewireStyles`, `@livewireScripts`, `@stack('scripts')`, `[x-cloak]` style, `{{ $slot }}` and `user-scalable=no` (an accessibility fix; pinch-zoom must work).
- `$shellDir`: `rtl` when the current locale's `Language` record has direction rtl. Reuse whatever helper the storefront uses. If none exists, add `TenantNavigation::direction()`.
- Pages must now wrap their content themselves **without** another `<main id="mn">`, because the layout provides it. When porting a Livewire view, drop its outer `<main id="mn">`.
- **Keep `resources/views/layouts/tenant.blade.php` and its partials untouched** until prompt 12. Unconverted Livewire pages still use it during the migration. Both layouts must work at the same time.
- Create `resources/views/tenant/layouts/guest.blade.php` for the login page: same head (tenant bundle), no shell, and the same body wrapper classes as `layouts/auth.blade.php` so the login looks identical.

## 3. Partials (`resources/views/tenant/layouts/partials/`)
- **`sidebar.blade.php`**: copy `layouts/tenant/sidebar.blade.php` markup 1:1, reading `$shell`.
  - The icon include becomes `<x-tenant::icon :name="…"/>`.
  - The DiceBear `<img>` becomes `<x-tenant::avatar :name="$shell['tenantName']" :seed="$shell['tenantId']" size="30"/>` (keep `.user-avatar-wrap` + `.status-indicator`).
  - `@livewire('tenant.widgets.account-setup-progress')` becomes `@include('tenant.layouts.partials.setup-progress')`.
  - Keep every link, badge (onboarding x/total, support unread), group, external link and `data-action`. Keep the brand text exactly.
- **`setup-progress.blade.php`**: the markup of `livewire/tenant/widgets/account-setup-progress.blade.php` with all `wire:*` removed.
  - Wrapper: `data-setup-progress data-url="{{ route('tenant.widgets.setup-progress') }}" data-poll="15000"`.
  - Toggle: `data-setup-toggle` (the expanded state is persisted in `localStorage['tenant-setup-expanded']`).
  - "Mark pages reviewed": `data-action-url="{{ route('tenant.widgets.setup-progress.pages-reviewed') }}" data-action-method="POST" data-success="emit:tenant:setup-progress:refresh"`.
  - Its inline `<style>` moves to `resources/css/tenant/components/setup-progress.css`.
- **`header.blade.php`**: copy the markup 1:1 from `$shell`.
  - Bell → `<x-tenant::notification-bell :unread="$shell['unreadNotifications']"/>`.
  - Avatar → `x-tenant::avatar`.
  - The logout `<form>` becomes `<x-tenant::btn variant="secondary" size="sm" data-action-url="{{ route('tenant.logout') }}" data-action-method="POST">Logout</x-tenant::btn>`. Keep a `<noscript>` form fallback.
- **`flash.blade.php`**: the hidden `#flash-status` (status / status_type). Add `#flash-setup-warning` / `#flash-setup-error` for `session('setup_warning')` / `session('setup_error')`, which `SetupGuard`/`EnforceOnboardingSetup` set and `core/toast.js showFlash()` shows as warning/error.
- **`compliance-modal.blade.php`**: rendered only when `$shell['compliance']['required']`. The composer computes it with the moved logic (§5). The markup is ported from `livewire/tenant/onboarding/tour.blade.php`; that file's `<style>` moves to `resources/css/tenant/components/compliance.css`.
  - It uses `x-tenant::modal static` auto-opened by `data-auto-open`, with no close button (acceptance is mandatory, as today).
  - It contains an `x-tenant::form action="{{ route('tenant.compliance.accept') }}" success="redirect reload-page"` with `x-tenant::checkbox name="accept" required` and links to each compliance page.
- **`setup-banner.blade.php`**: the `showSetupBanner` markup from `tour.blade.php`, shown when `$shell['setupBanner']`.

## 4. Shell JS (`resources/js/tenant/shell/`)
- `layout.js` comes from prompt 01. Add `setup-progress.js`:
  - poll the widget's JSON every 15 s **only while `document.visibilityState === 'visible'`**
  - refresh immediately on `tenant:setup-progress:refresh`
  - patch the percentage, bar width (`--p`), step list and done states **in place** (no full HTML swap, so the expanded state and focus survive)
  - `data-setup-toggle` expands/collapses.
- `notification-bell.js` (component module for `x-tenant::notification-bell`, registered in the registry):
  - `getEcho().private(\`tenant.${tenantId}.notifications\`).listen('.notification.created', e => { unread++; render(); toast.info(e.message, e.title); })`
  - listens to `tenant:notification-read` (emitted by the notifications page) → `unread--`
  - badge text is `99+` when over 99
  - realtime notifications use the themed toastr instead of the bespoke Alpine toast, for one consistent alert style. The `.notif-toast` CSS is not ported.
- `x-tenant::notification-bell` component: the same anchor/badge markup as the global bell, **with no Alpine and no script**. The badge is `hidden` when 0.

## 5. Backend

### 5.1 Compliance + first-run tour redirect (replaces `OnboardingTour`)
- Create `app/Services/Tenant/ComplianceService.php` and move, **unchanged**, `compliancePages()`, `compliancePagesExist()`, `complianceVersion()`, `hasAcceptedCurrentCompliance()` and the accept write (`TenantModel::saveData(...)`) out of `OnboardingTour`.
- Controller `App\Http\Controllers\Tenant\Panel\Shell\ComplianceController`:
  - `accept(AcceptComplianceRequest)`. The rule is `accept => accepted`, message "Please confirm you have read and accept the compliance documents." (the exact current text).
  - It returns `success('Compliance documents accepted.', redirect: <tour-redirect-or-current>)`.
  - Routes: `POST /admin/compliance/accept` (`tenant.compliance.accept`) and `POST /admin/compliance/accept/validate` (`tenant.compliance.accept.validate`). Both are **excluded from `tenant.setup.enforce`**, via `->withoutMiddleware('tenant.setup.enforce')` (like `tenant.verification.send`).
- Middleware `App\Http\Middleware\EnsureTenantTourSeen` (alias `tenant.tour`), appended to the authenticated panel group:
  - passes when `routeIs('tenant.onboarding')`, the request `expectsJson()`, the method is not GET, or compliance is still pending (the modal must show first — the same order as `mount()`)
  - otherwise redirects to `tenant.onboarding` `['tab' => 'tour']` when `auth('tenant')->user()->tour_seen_at === null`
  - This moves the Livewire `mount()` redirect to where it belongs (it currently runs as a side-effect of rendering the layout).
- `ShellComposer` adds `compliance` (`required`, `pages`) and `setupBanner` (`tour_seen_at !== null && ! routeIs('tenant.onboarding') && done < total`).

### 5.2 Setup-progress widget endpoints
Controller `App\Http\Controllers\Tenant\Panel\Shell\SetupProgressController`:
- `show()` → `TenantNavigation::setupProgress()` as JSON. Route `GET /admin/widgets/setup-progress` (`tenant.widgets.setup-progress`).
- `pagesReviewed(TenantPanelService $service)` → `$service->markDefaultPagesReviewed()`, then `success('Default pages marked as reviewed.')`. Route `POST /admin/widgets/setup-progress/pages-reviewed` (`tenant.widgets.setup-progress.pages-reviewed`).

Both sit inside the authenticated group **with `->withoutMiddleware('tenant.setup.enforce')`**, because the widget appears on the onboarding page too.

### 5.3 Login / logout (tenant-only, the shared view untouched)
- `App\Http\Controllers\Tenant\Panel\Auth\LoginController`:
  - `show()`: redirect to the dashboard if authenticated; otherwise `view('tenant.pages.auth.login')`.
  - `validateLogin(LoginRequest)` → `validFormResponse()`.
  - `login(LoginRequest)`: the **exact** logic from `LoginPage::login()`, including the three `email` error messages (thrown as `ValidationException::withMessages(['email' => …])` → 422). Then `Auth::guard('tenant')->login($admin, $request->boolean('remember'))`, `session()->regenerate()`, `success('Welcome back, '.$admin->name.'.', redirect: redirect()->intended(route('tenant.dashboard'))->getTargetUrl())`.
  - `logout(Request)`: the same three lines as the closure, then `success('You have been signed out.', redirect: route('tenant.login'))`.
- `LoginRequest`: copy the rules from `LoginPage` (email/password/remember).
- Routes (in `routes/tenant_panel.php`; names unchanged):
  - `GET /admin/login` → `show` (`tenant.login`, `guest:tenant`)
  - `POST /admin/login` → `login` (`tenant.login.attempt`, `guest:tenant`, `throttle:6,1`) — **new throttle**; the Livewire version had none
  - `POST /admin/login/validate` (`tenant.login.validate`, `guest:tenant`, `throttle:tenant-validate`)
  - `POST /admin/logout` → `logout` (`tenant.logout`, `auth:tenant`)
- View `resources/views/tenant/pages/auth/login.blade.php` extends `tenant.layouts.guest`.
  - Port the **markup and look** of `livewire/auth/panel-login.blade.php` exactly as the tenant variant renders it today: read which variables `LoginPage::render()` passes (title, subtitle, brand …) and hard-code the same values.
  - Use `x-tenant::form validate="…" success="redirect"`, `x-tenant::input type=email`, `x-tenant::input type=password toggle`, `x-tenant::checkbox name=remember` and `x-tenant::submit`.
  - JS entry `resources/js/tenant/pages/auth/login.js` (autofocus email; nothing else). Register it in `vite.config.js`.
- On a successful login the dashboard shows the "Welcome back" toast via the flash bridge.

## 6. Existing controller pages → new layout
Switch these already-controller-based views from `@extends('layouts.tenant')` to `@extends('tenant.layouts.app')`. **Their full AJAX/component conversion happens in their section prompts.** Here they only need to keep working without Livewire/Alpine present:
- `tenant/badge/show.blade.php`
- `tenant/customer/customer-create.blade.php`
- `tenant/customer/customer-detail.blade.php`
- `tenant/product/add-edit-own-product.blade.php`
- `tenant/setting/account-settings.blade.php`
- `tenant/setting/compliance-center.blade.php`

Because `customer-detail` (15 Alpine directives) and `customer-create` (3) depend on Alpine, **do not switch those two here**. They switch in prompt 06 together with their conversion.

## 7. Vite entries to add
```
'resources/js/tenant/pages/auth/login.js',
```
(`shell/*` and components are imported through `resources/js/tenant/app.js` / the registry, not as separate entries.)

## 8. Verification
1. `/admin/login` looks identical to before.
   - Wrong password → inline error under email plus a toast.
   - 7 rapid attempts → 429 toast.
   - Success → dashboard with the "Welcome back" toast.
2. On a page using the new layout (e.g. `/admin/settings/account`):
   - no `livewire.js` request, no Google Fonts request, no DiceBear request
   - the sidebar collapses/expands (the state persists), mobile drawer, groups, theme toggle (reload → no flash, cookie set) and date pill all work.
3. Setup widget: polls only while the tab is visible; "mark pages reviewed" toasts and refreshes.
4. Bell: trigger a `TenantNotification` → toastr info plus badge increment through Reverb (verifies the fixed `/tenant/broadcasting/auth` endpoint in the Network tab).
5. Fresh tenant with `tour_seen_at = null` → any GET panel page redirects to `/admin/onboarding/tour`; AJAX calls are not redirected.
6. Pending compliance version → a static modal on every page; accepting without the checkbox → a field error; accepting → toast, then the tour redirect if applicable.
7. Pages still on the old Livewire layout keep working unchanged.
