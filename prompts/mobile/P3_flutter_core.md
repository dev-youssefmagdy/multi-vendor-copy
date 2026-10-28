Role: Senior Flutter Architect. Work in application/ only. Read 00_MASTER_PLAN.md, 01_BACKEND_CONTRACT.md. Replace counter template.
Create: pubspec deps; lib/core (config, env: PLATFORM_API_BASE via --dart-define, errors, logger w/o secrets, dio client + interceptors
(tenant/header/retry/timeout/refresh/401), connectivity); lib/features/tenant (Tenant, TenantConfig, TenantResolutionService,
TenantRepository, TenantStorageService (secure+prefs, per-tenant namespacing), tenant controller, TenantGuard, switch workflow);
lib/features/deeplink (DeepLinkService with app_links, URL normalizer: trailing slash, encoding, query, product/category/cart/checkout/account/home);
lib/features/auth (per-tenant token vault, repo); go_router with redirect guard; startup flow (stored → deeplink → validate → bootstrap →
offline fallback); store-entry screen (URL / code / recent stores); invalid/suspended/offline screens; theme engine skeleton
(ThemeRegistry, tokens from bootstrap colors, safe defaults) . Mock implementations behind interfaces for tests.
Tests: unit (models, parsing, storage, switching, isolation) + widget (first launch, invalid tenant, offline). Run flutter analyze + flutter test.
