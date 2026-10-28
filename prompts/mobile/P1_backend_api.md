Role: Senior Laravel Backend. Read docs/mobile/00_MASTER_PLAN.md, 01_BACKEND_CONTRACT.md, 01_SCREEN_THEME_MATRIX.md (gaps),
CLAUDE.md order rules (docs/ORDER_FORMULAS.md before touching checkout/orders; use OrderPricing/OrderComposer).
Implement: routes/mobile.php (register in bootstrap/app.php under /api/mobile/v1), middleware ResolveMobileTenant (X-Tenant + token
binding, rejects suspended), tenant migration mobile_access_tokens, Mobile controllers per contract, resolve endpoint against the
Domain model (central), bootstrap using HomeVariantResolver/SectionRegistry/app-theme tables from P2 (coordinate: P1 creates the
migrations+models for mobile_app_settings & mobile_app_theme; P2 builds UI), well-known files via env MOBILE_ANDROID_PACKAGE,
MOBILE_ANDROID_SHA256, MOBILE_IOS_APP_ID. Rate-limit resolve/auth. Pest/PHPUnit feature tests incl. cross-tenant token rejection.
Do not break /api/v1. Run only the new tests. Report endpoints, files, tests.
