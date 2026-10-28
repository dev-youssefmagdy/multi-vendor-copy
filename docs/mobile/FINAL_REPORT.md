# Mobile app - final QA / security report (P7)

Scope: Flutter app `application/`, backend `/api/mobile/v1` (`routes/mobile.php`, `app/Http/Controllers/Mobile/*`,
`app/Services/Mobile/*`, panel pages). Statements below are limited to what was actually run.

## Architecture
- One app for all tenants. Startup: deep link -> stored tenant -> `GET /resolve` -> `GET /bootstrap` (ETag) -> offline cache fallback.
  `TenantController` owns the flow; `ApiClient` (dio) adds `X-Store`, `X-Tenant`, `Authorization` from a per-tenant `TokenVault`.
- Riverpod, go_router, dio, flutter_secure_storage (tokens), shared_preferences (non-secret, keys `tenant:<id>:*`), app_links.
- Themes: `ThemeRegistry` (ecommet v1, elora v1-v6, souqify v1-v6) + `ThemeSkin` tokens + section widget registry (`lib/themes/*`).
- Backend: stateless token auth (`mobile_access_tokens`, hashed, per-tenant DB), `ResolveMobileTenant` (X-Tenant, 404/403 suspended),
  `AuthenticateMobileToken`, server-side `MobileCart`; pricing/orders via existing `OrderComposer`/`OrderPricing` (no client math).

## Features
Home (13 variants), categories/listing/filters, search + autocomplete, product + reviews, cart, checkout (quote, gateways, coupon,
address), WebView payment with server-side verification, auth (register/login/forgot/reset/delete account), profile, addresses,
favorites, orders (list/detail/tracking/invoice/cancel/reorder), returns, CMS pages, locale/currency/country, dark mode,
connectivity banner, maintenance / force-update / suspended / invalid / offline screens, deep links.

## Backend changes / ops
- New tenant tables: run `php artisan tenants:migrate` (migrations `2026_10_28_000002_create_mobile_app_tables`, `..._000003_create_mobile_cart_and_device_tables`).
- Env: `MOBILE_ANDROID_PACKAGE`, `MOBILE_ANDROID_SHA256`, `MOBILE_IOS_APP_ID`, `MOBILE_DEEP_LINK_SCHEME`, `MOBILE_PLAY_STORE_URL`, `MOBILE_APP_STORE_URL`.
- Panel: Mobile App settings and App Themes pages. See `01_BACKEND_CONTRACT.md`.

## Android / iOS / DNS
See `DEEPLINKS.md`. Build with `--dart-define=PLATFORM_API_BASE=https://<platform>`. Wildcard DNS + TLS for `*.<platform-domain>`;
well-known files served publicly without redirect.

## Commands
```
cd application && flutter analyze && flutter test
cd application && flutter build apk --debug --dart-define=PLATFORM_API_BASE=https://<platform>
php artisan test tests/Feature/Mobile
php artisan route:list --path=api/mobile
```

## Results actually obtained
- `flutter analyze`: no issues. `flutter test`: all tests pass (127 before P7; plus matrix coverage + scenario tests added in P7).
- `flutter build apk --debug`: succeeded (after a disk-full incident corrupted the gradle cache; `./gradlew --stop` fixed it). Not installed or run.
- `php artisan test tests/Feature/Mobile`: 23 passed (189 assertions). `route:list --path=api/mobile`: 63 routes.
- Not re-run by us: the full web suite. Four PRE-EXISTING web failures are not ours (OrderCancellationTest x3,
  StockRestoreTest::order_columns_helpers..., verified failing at commit 68db3e41).

## Matrix coverage
`application/test/unit/matrix_coverage_test.dart` maps S02-S22 to routes, V01-V13 to registry + skins, W01-W14 to widget classes,
A01-A04/C04-C07 to wiring. These are structural checks; behaviour is covered by widget/unit suites. S09 (social login) is NOT built.
G01-G06 were resolved or documented in P1/P5 (per-variant allowed keys filtered in bootstrap; `BootstrapController`).

## Security audit (code review evidence)
| Item | Result |
|---|---|
| Tokens in secure storage only | `TokenVault` -> `SecureStore`; prefs hold tenant/bootstrap/preferences only. OK |
| No host injection | `AppConfig.platformApiBase` from `PLATFORM_API_BASE`; `api_base` from server is parsed but never used; `?store=` goes through `normalizeHost` then `/resolve`; `scheme://payment` never switches store. OK |
| No secrets in logs | `AppLogger.redact`; API logger logs method/path/status only. OK |
| Tenant<->token binding | token looked up in the tenant's own DB (`AuthenticateMobileToken`); cross-tenant cart/checkout test exists. OK |
| Payment success server-only | `PaymentResultScreen` polls `payment-status`; deep-link `status` is a hint. Test exists. OK |
| Rate limits | resolve 30/min, auth 10/min, pay and checkout 20/min. OK |
| Suspended tenant | middleware 403 `store_suspended`; app suspended screen. OK |
| Password reset | 48-char random, sha256 stored, 60 min TTL, single use, revokes all tokens. Note: no per-token attempt counter beyond the 10/min throttle (entropy makes brute force infeasible) |
| Tenant switch | cancels in-flight requests, clears session tenant, bumps `sessionEpoch`, tokens/caches keyed by tenant id. Tested |
| Android | FIXED: `allowBackup=false`, `fullBackupContent=false`, `usesCleartextTraffic=false` in main manifest; debug overlay re-enables cleartext for dev only |
| iOS ATS | No `NSAppTransportSecurity` exceptions (default ATS enforced). OK |

Defects found/fixed: Android backup + cleartext defaults (fixed). Release still uses debug signing (template TODO, documented).

## KNOWN GAPS
- Not run on any device/emulator; not tested against a real backend or real payment gateways; deep links unverified with Google/Apple.
- Placeholders needing input: platform domain, package/bundle id (`com.yourcompany.storeapp`), Android SHA-256, Apple Team ID, store URLs, URL scheme.
- Fonts are not bundled (Flutter falls back to the platform font; a skin `fontFamily` is optional); backend `lang/` files are gitignored (translations must be provided at deploy).
- Not built: image search, social login (Google/Apple), push delivery (no Firebase). Client registration code exists but is gated by `--dart-define=PUSH_ENABLED=true` and a `PushTokenSource` (default: none), so it is a no-op.
- Custom-domain verified deep links are not possible without per-tenant builds (see DEEPLINKS.md).
- Payment URL returned by the server is not scheme-restricted client-side (trusted backend).
- iOS was never built (no macOS toolchain).

## P8: splash, store entry, gap sweep
- Splash: Android 12+ splash API (`values-v31`, `values-night-v31`) + legacy `launch_background` (light/dark colour), iOS LaunchScreen
  with a neutral placeholder mark; label/CFBundleDisplayName = `Store App`. Hand-edited (flutter_native_splash not used).
  Flutter `SplashScreen` shows cached store logo (`branding.splash_image_url` > logo), name (`branding.app_name`) and primary colour
  only when the store being opened is known (`TenantState.brandingTenant`); neutral otherwise; fade into home.
- Backend: bootstrap `branding.splash_image_url` and `branding.app_name` (falls back to store name); test added.
- Store entry: platform branding, live normalisation preview, Paste button, clipboard suggestion (asks before use, only for dotted/URL text),
  recents with logos (letter fallback), help sheet, RTL-safe paddings. A deep link while it is shown resolves and replaces it. Tests in
  `test/widget/store_entry_test.dart`. Invalid/suspended/offline remain dedicated screens.
- Gap sweep: share via share_plus (product link `https://<domain>/products/<slug>`, clipboard fallback); payment return link arriving
  outside the WebView routes to `/payment/result` (server still verifies); placeholder launcher icons (Android mipmaps, iOS AppIcon);
  push registration client (gated, see above).
- Skipped: QR scanning (mobile_scanner needs camera permissions/native setup and cannot be verified without a device); Install Referrer
  deferred deep link (no mature cross-platform plugin; Android-only play referrer wrappers are thin and unverified) - documented only;
  fonts (none bundled, system fallback); iOS native files unverified (no macOS).

## Production checklist
1. Set domain, package/bundle ids, SHA-256, Team ID, store URLs; verify well-known files with curl/adb/device.
2. Real release signing config; Play App Signing fingerprint in `MOBILE_ANDROID_SHA256`.
3. `php artisan tenants:migrate`; set `MOBILE_*` env; wildcard TLS.
4. Build with `PLATFORM_API_BASE` over HTTPS; bundle fonts; deploy lang files.
5. Test every payment gateway end to end (success, cancel, pending) on devices.
6. Run on physical Android + iOS; exercise tenant switching, suspension, force update.
7. Fix or accept the 4 pre-existing web test failures.
