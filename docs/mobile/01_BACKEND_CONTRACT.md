# Mobile API contract (`/api/mobile/v1`, JSON, all responses `{success, data?, message?, errors?}`)

Headers: `Accept: application/json`, `X-App-Version`, `X-Platform`. Tenant routes add `X-Tenant: <tenant_id>`;
protected add `Authorization: Bearer <mobile_token>`. Language `Accept-Language`, currency `X-Currency`.

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET | /resolve?host=&code= | none (throttled) | host/store-code → tenant summary (status active/suspended/not_found) |
| GET | /bootstrap | tenant | settings, theme, variant, sections, colors, locales, features, config_version (ETag/304) |
| POST | /auth/register, /auth/login, /auth/logout, /auth/refresh, /auth/forgot-password, DELETE /auth/account | tenant | token auth, tenant-bound |
| GET | /home/sections/{key} | tenant | data for one home section (products, banners, categories…) |
| GET | /pages/{slug}, /menus | tenant | CMS pages/footer/header menus |
| * | catalog/cart/favorites/addresses/coupon/checkout/orders/returns/profile/reviews | tenant(+token) | thin wrappers around existing services of `/api/v1` & Livewire pages; must reuse OrderPricing/OrderComposer |
| GET | /orders/{uuid}/tracking, /returns, /returns/{id}, POST /orders/{uuid}/return | token | returns & tracking |
| GET | /search, /search/autocomplete | tenant | text search (image search optional) |
| POST | /device-tokens | token | push token registration (FCM) — optional phase |
| GET | /.well-known/assetlinks.json, /apple-app-site-association | public | deep link verification |
Payment: gateways redirect-based → open in in-app WebView with success/cancel return URLs; server verifies. Never mark paid client-side.
