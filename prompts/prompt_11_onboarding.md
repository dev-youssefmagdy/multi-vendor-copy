# Prompt 11 — Onboarding Page (Tour + Setup tabs)

Requires prompts 01–03 and 09 (the logo-builder partial + self-hosted logo fonts). Follow the **Standard Conversion Recipe** (prompt 00).
Controller: `app/Http/Controllers/Tenant/Panel/Onboarding/OnboardingController.php`. View: `resources/views/tenant/pages/onboarding/index.blade.php`. JS: `resources/js/tenant/pages/onboarding/index.js`.

## Context (audited): `App\Livewire\Tenant\Onboarding\OnboardingPage` (410 lines)
- **Route:** `GET /admin/onboarding/{tab?}` with `->where('tab','tour|setup')`, named `tenant.onboarding`. Not under `tenant.setup.enforce` blocking (`EnforceOnboardingSetup` lets the onboarding route pass).
- **`mount($tab)`:**
  - normalises the tab
  - **if `setup`, calls `markTourSeen()`** (`tour_seen_at = now()`)
  - loads the 10 logo settings from `Setting` with defaults: `logo_mode` image unless 'text'; `logo_color` `#111827`; `logo_bg_color` `#ffffff`; `logo_shape` rectangle unless 'rounded'; `logo_font_ar` `cairo`; `logo_font_en` `poppins`
  - `paymentReadinessSkipped` from `payment_readiness_skipped_at`
- **Tour:**
  - `steps()` (a data list)
  - `currentStep` (`wire:click="$set('currentStep', i)"`)
  - `nextStep()`: advances, or calls `completeTour()` on the last step
  - `skipTour()` / `completeTour()`: `markTourSeen()` then go to the setup tab
- **Setup:**
  - `setupItems()`, `allItemsDone()`, `logoIsSet()`, `activeTheme()`, `paymentGatewayConfigured()`, `languageConfigured()`, `paymentReadinessItems()`
  - `saveLogo()`: validate + `TenantPanelService::saveAppearanceSettings(...)`, toast "Logo saved successfully."
  - `skipPaymentReadiness()`: `payment_readiness_skipped_at = now()`
  - `dismissSetup()`: `setup_dismissed_at = now()`, `Tenant::saveData($tenantId, ['launch_ready' => true])`, then redirect to the dashboard
- **View** `livewire/tenant/onboarding/page.blade.php` (319 lines, 1 script) + `onboarding/icons.blade.php`. Tabs link with `wire:navigate`.
- `resources/js/app.js` has a `livewire:navigated` hook for `.ob-upload-input` file-name display. It is ported into the page JS here.
- `SetupGuard` redirects to `tenant.onboarding` with `['tab'=>'setup','item'=>…]` and `setup_return_url`. Keep supporting the `item` query (highlight or scroll to that item) and the "return" link if the view uses it.

## 1. Service
Move all the read helpers into `App\Services\Tenant\OnboardingService`, **unchanged**:
- `steps()`
- `setupItems()`, `allItemsDone()`, `logoIsSet()`, `activeTheme()`, `paymentGatewayConfigured()`, `languageConfigured()`, `paymentReadinessItems()`
- the logo-settings loader with its defaults
- `markTourSeen()`, `skipPaymentReadiness()`, `dismissSetup()`

`SetupGuard` and `TenantNavigation` may already duplicate some checks. Don't refactor them in this prompt, just keep results identical.

## 2. Controller & routes (`routes/tenant_panel.php`)
```
GET  /admin/onboarding/{tab?}                   OnboardingController@show                  tenant.onboarding            (where tab tour|setup)
POST /admin/onboarding/tour/complete            OnboardingController@completeTour          tenant.onboarding.tour.complete
POST /admin/onboarding/logo                     OnboardingController@saveLogo              tenant.onboarding.logo
POST /admin/onboarding/logo/validate            OnboardingController@validateLogo          tenant.onboarding.logo.validate
POST /admin/onboarding/payment-readiness/skip   OnboardingController@skipPaymentReadiness  tenant.onboarding.payment-readiness.skip
POST /admin/onboarding/dismiss                  OnboardingController@dismiss               tenant.onboarding.dismiss
```
- All new POST routes are `->withoutMiddleware('tenant.setup.enforce')` (they are part of completing setup) and excluded from `EnsureTenantTourSeen` (prompt 03 already skips non-GET requests).
- `show($tab = 'tour')`: the same normalisation. **If `setup`, `markTourSeen()`** (keep this side effect on GET, exactly as today). Pass steps, setup items, logo data, `LOGO_FONTS`, the payment readiness items and the flags.
- `completeTour()` (used by both Skip and Finish) → `markTourSeen()`, then `success('Tour completed.', redirect: route('tenant.onboarding', ['tab' => 'setup']))`. The Skip button can send `{skipped:1}` to toast "Tour skipped." instead, or send nothing.
- `saveLogo(SaveLogoRequest)`:
  - rules copied verbatim from `saveLogo()` (the same set as Appearance's general logo rules; share a `LogoRules` trait with prompt 09's `UpdateAppearanceGeneralRequest`)
  - `saveAppearanceSettings(...)`
  - `success('Logo saved successfully.', data: ['setup' => <recomputed setup items/progress>])`
  - the JS re-renders the checklist item and emits `tenant:setup-progress:refresh`
- `skipPaymentReadiness()` → `success('Payment readiness marked as complete.', data: ['setup' => …])`, then the same re-render + refresh event.
- `dismiss()` → the service `dismissSetup()` (including `launch_ready`), then `success('Setup complete — your store is ready.', redirect: route('tenant.dashboard'))`. Use a confirm only if the view had one (it didn't; don't add friction).

## 3. View
- Port `onboarding/page.blade.php` 1:1 into `tenant.layouts.app`. The tab links become plain `<a>` (`x-tenant::tabs mode="link"`, keys `tour|setup`).
- **Tour stepper is client-side** (it has no server state besides `tour_seen_at`):
  - render all steps
  - `pages/onboarding/index.js` shows the current step, dot navigation (replaces `$set('currentStep')`) and Next
  - on the last step, Next posts `completeTour` via `core/actions`
  - Skip posts the same endpoint
  - the current step persists in `sessionStorage` so a reload doesn't restart the tour
- **Setup checklist:**
  - Each item's action buttons stay links (theme, payment gateway, languages, pages …), exactly as today.
  - The logo item embeds `@include('tenant.pages.store._logo-builder', [...])` (prompt 09) inside an `x-tenant::form action=route('tenant.onboarding.logo') validate=… success="none"` (the JS handles the re-render). **This loads the self-hosted logo fonts only here and on Appearance.**
  - `.ob-upload-input` file-name display (from `resources/js/app.js`) is handled by `x-tenant::image-upload` in the logo builder, so it is not needed separately.
  - Payment readiness "Mark as complete" is a `data-action-url` button (loading state via `setBusy`, replacing the `wire:loading` spans).
  - Dismiss buttons (2 places) are `data-action-url` to `tenant.onboarding.dismiss`.
  - `?item=<key>`: scroll to and highlight that checklist item on load (flash ring for 2 s). Show the `setup_error`/`setup_warning` flashes (prompt 03 flash partial) as toasts.
- Move `onboarding/icons.blade.php` to `resources/views/tenant/pages/onboarding/_icons.blade.php`.
- The inline `<script>` and any `style="…"` static styles move to `pages/onboarding/index.js` and `resources/css/tenant/pages/onboarding.css`.

## 4. Vite entry
```
'resources/js/tenant/pages/onboarding/index.js',
```

## 5. Verification
1. Fresh tenant (`tour_seen_at` null) → redirected to the tour. Next through all steps → Finish → setup tab. `tour_seen_at` is set.
2. Opening `/admin/onboarding/setup` directly also sets `tour_seen_at` (the side effect is preserved).
3. Save a text logo and an image logo from onboarding → toast. The checklist item flips to done without a reload. The sidebar setup widget updates immediately.
4. `SetupGuard` redirect (e.g. visiting Orders without a gateway) lands on `?tab=setup&item=payment_gateway` with the warning toast and the item highlighted.
5. Dismiss → dashboard + toast. `StoreLaunchGate` passes (`launch_ready = true`).
6. Logo fonts load from `/build/assets` only on this page and Appearance.
