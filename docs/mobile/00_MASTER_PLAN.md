# Mobile App — Master Plan (single source of truth)

One Flutter app (`application/`) serves every tenant. It re-implements the storefront themes
(`ecommet`, `elora` v1–v6, `souqify` v1–v6) natively. **No custom/blade-uploaded themes in the app.**

## Discovered facts (verified in repo)
- Tenancy: stancl, per-tenant DB, `InitializeTenancyByDomain`. Central routes: `routes/central.php`.
- Existing `/api/v1` (routes/api.php): catalog (categories, products, reviews), cart add/remove/update,
  favorites, addresses, checkout, coupon, profile, orders. Auth header today = `Bearer {tenant_id}` (NOT a user token)
  via `IdentifyTenantByApiToken`; customer routes use `auth:storefront` (session guard) → **unusable from mobile**.
- NOT existing: tenant resolve-by-domain endpoint, customer login/register/token API, home layout/sections API,
  app settings, app theme variant selection, deep-link association files.
- Themes: `resources/views/themes/{ecommet,elora,souqify}`; home variants = `HomeVariant` (central DB, theme_slug+key,
  `sections`, `colors`); tenant choice = `TenantHomeVariant`; section order/visibility = `TenantPageSection`;
  colors = `tenant_theme_colors`. Resolved by `App\Services\Tenant\HomeVariantResolver` + `SectionRegistry`.
- Flutter 3.47.1 installed; `application/` is the untouched counter template.

## Decisions
1. **Backend namespace `/api/mobile/v1`** (new routes file `routes/mobile.php`, new controllers `App\Http\Controllers\Mobile\*`).
   Reuse existing services/repositories; no duplicated formulas (see CLAUDE.md: pricing/orders go through
   `OrderPricing`/`OrderComposer` etc. — read `docs/ORDER_FORMULAS.md` before touching checkout/orders).
2. **Tenant identity**: app sends `X-Store: <canonical host>`; central endpoint `GET /api/mobile/v1/resolve?host=` validates
   against registered tenant domains (Domain model) and returns `{tenant_id, domain, name, logo, status, api_base}`.
   `api_base` is ALWAYS the platform's central API origin (never taken from a deep link). All later calls carry
   `X-Tenant: <tenant_id>` + user token; middleware re-verifies tenant ↔ token binding server-side.
3. **Auth**: new tenant-DB table `mobile_access_tokens` (hashed, tenant-scoped, device name, expiry, revoke) —
   login/register/logout/refresh/forgot-password under `/api/mobile/v1/auth/*`. Token bound to tenant_id + customer id.
4. **Mobile config is a tenant-panel feature**: new panel page "Mobile App" (`/admin/store/mobile-app`) with settings
   (app name, logo/splash, primary/secondary colors override, enabled features, bottom-nav items, currency/locale,
   support links, store-listing links, force-update min version, maintenance flag) stored in tenant DB table
   `mobile_app_settings` (key/value json). Separate page "App Themes" (`/admin/store/mobile-themes`) to pick
   app theme + home variant (only built-in themes; custom excluded) → table `mobile_app_theme` (theme_slug, variant_key).
   Mobile theme is INDEPENDENT of web storefront theme.
5. **Bootstrap endpoint** `GET /api/mobile/v1/bootstrap` returns: tenant, settings, theme slug, variant key, resolved
   section list (ordered, visible, with per-section config/data keys), colors, locales, currencies, feature flags,
   `config_version` (ETag). Flutter renders sections via a **section-widget registry** (no remote code).
6. **Flutter architecture**: Riverpod (codegen-free), go_router, dio, flutter_secure_storage, shared_preferences,
   app_links, cached_network_image, freezed-free hand-written models (keep build simple). Feature-first folders.
   Theme engine: `ThemeRegistry[themeSlug][variantKey] -> HomeLayout(sections→widgets) + header/footer/nav style + tokens`.
7. **Deep links**: App Links / Universal Links on platform wildcard domain (`*.<platform-domain>`) + custom-domain
   fallback strategy (web landing page with `intent://` / store badge + `?store=` handoff; manual store-code entry;
   optional Play Install Referrer for Android). Backend serves `/.well-known/assetlinks.json` and
   `/.well-known/apple-app-site-association` from env config. Custom domains are NOT auto-verified: document it.
8. **Isolation**: all caches/keys namespaced `tenant:<id>:`; tenant switch = cancel dio tokens, reset providers, drop
   tenant token from active session (per-tenant tokens stored separately, restored only for same tenant).

## Phases / agents (run in order; each reads this file + its own prompt; reports files/tests/issues)
| # | Prompt | Output |
|---|--------|--------|
| P0 | `P0_inventory.md` | `docs/mobile/01_SCREEN_THEME_MATRIX.md` — exhaustive screens, theme×variant×section matrix, section→API data map |
| P1 | `P1_backend_api.md` | mobile API + tokens + resolve + bootstrap + well-known + tests |
| P2 | `P2_backend_panel.md` | tenant panel pages: Mobile App settings + App Themes variants (+ lang keys, permissions, help) |
| P3 | `P3_flutter_core.md` | pubspec, core, tenant, deeplink, storage, api, auth, router, theme engine skeleton + tests |
| P4 | `P4_flutter_screens.md` | all commerce screens (shared logic, themed skins) |
| P5 | `P5_flutter_themes.md` | every theme × variant home layout, header/footer, section widgets |
| P6 | `P6_platform_linking.md` | Android/iOS config, well-known, landing fallback, docs |
| P7 | `P7_qa_security.md` | analyze/test, isolation review, matrix coverage check, final report |

Token-saving rules for all agents: read only files named in your prompt; use grep before reading; never re-read the
master plan twice; commit-sized steps; run `flutter analyze` / targeted tests, not full builds, unless asked.
