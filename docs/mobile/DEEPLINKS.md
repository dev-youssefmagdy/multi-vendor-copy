# Mobile deep links (P6)

Status: configuration and backend (including per-tenant white-label apps, backend phase 8) are implemented. NOTHING is
verified on a device or against Google/Apple: that needs the REQUIRED INPUTS below. Backend output is covered by
`tests/Feature/Mobile/MobileApiTest.php` and `tests/Feature/Mobile/TenantMobileAppTest.php`.

## REQUIRED INPUTS for the platform app (not provided yet - placeholders in use)
| Input | Where it goes |
|---|---|
| Platform domain (e.g. `platform.example.com`) | `application/tenants/index.json` `platformDomain` (Android); `application/ios/Flutter/AppIdentity.xcconfig` `APP_ASSOCIATED_DOMAIN` (`*.domain`) |
| Final Android package / iOS bundle id (placeholder `com.yourcompany.storeapp`) | `application/tenants/platform/tenant.json` (`android.applicationId`, `ios.bundleId`); `AppIdentity.xcconfig` `APP_BUNDLE_ID`; backend `MOBILE_ANDROID_PACKAGE` |
| Android SHA-256 cert fingerprint(s) | backend `MOBILE_ANDROID_SHA256` (comma separated) |
| Apple Team ID | backend `MOBILE_IOS_APP_ID=<TeamID>.<BundleID>`; Xcode signing team |
| Custom scheme (default `storeapp`) | `tenants/platform/tenant.json` `android.urlScheme` / `ios.urlScheme`, `AppIdentity.xcconfig` `APP_URL_SCHEME`, backend `MOBILE_DEEP_LINK_SCHEME` |
| Store URLs (optional) | backend `MOBILE_PLAY_STORE_URL`, `MOBILE_APP_STORE_URL` |

Android ids now live in `application/tenants/<id>/tenant.json` (one product flavor per tenant, see
[WHITE_LABEL_ARCHITECTURE.md](WHITE_LABEL_ARCHITECTURE.md)); the generic app is the `platform` tenant. iOS still uses
`AppIdentity.xcconfig` (per-tenant generation is the iOS phase). The Kotlin source package `com.example.application` is only the
`namespace`; it does not need to match the store id.

## What is wired
- Android: `AndroidManifest.xml` has an `autoVerify` https filter for `${appLinkHost}` and a custom-scheme filter
  `${urlScheme}://` (payment result and landing "Open in app"). Per flavor (`android/app/build.gradle.kts`):
  `platform` → `appLinkHost = *.<platformDomain>`; a white-label store → its `store.host`, plus its
  `store.extraLinkHosts` from the generated `android/app/src/<id>/AndroidManifest.xml`.
- iOS: `Runner/Runner.entitlements` (`applinks:` + `webcredentials:` for `$(APP_ASSOCIATED_DOMAIN)`), `Info.plist` URL scheme,
  `FlutterDeepLinkingEnabled=false` (app_links handles links), `CODE_SIGN_ENTITLEMENTS` set on Runner configs.
  White-label iOS builds replace the entitlements in their isolated workspace with literal entries for the
  tenant's `store.host` + `extraLinkHosts` (see WHITE_LABEL_ARCHITECTURE.md); the committed file stays the platform app's.
- Flutter: `lib/features/deeplink` (app_links) already parses https links by host, honours `?store=`, and custom-scheme
  links as `scheme://<store-host>/<path>`; `scheme://payment/...` is left to the payment WebView.
- Backend: `/.well-known/assetlinks.json`, `/.well-known/apple-app-site-association` (and `/apple-app-site-association`),
  `WellKnownController` + `App\Services\Mobile\MobileAppLinks`. Central domain / unknown host: the platform app from
  `config/mobile.php`. Tenant domain (subdomain **or** custom domain, looked up in the `domains` table): that tenant's
  white-label app (if configured) **plus** the platform app. Empty documents when nothing is configured. Public, no
  redirect, `application/json`.
- Landing: `GET /app?path=/products/x` on every tenant domain (`AppLandingController`, standalone view
  `resources/views/storefront/app-landing.blade.php`, outside theme layouts/routes): Open in app (`scheme://<host>/<path>`),
  store buttons, continue in browser, and the store code for manual entry in the app. `path` is sanitised (no open redirect).
  When the store has its own app: "Open in app" uses the store's scheme and the install buttons its store URLs (each falls
  back to `MOBILE_PLAY_STORE_URL` / `MOBILE_APP_STORE_URL`), plus a small "open in the multi-store app" link with the
  platform scheme.

## White-label apps (one app per store)
- A white-label binary is locked to `TENANT_HOST` (build time). Links to its own host or `extraLinkHosts` open in the
  app; links (or `?store=` handoffs) to any other store open in the external browser and never switch the store.
- Each white-label app has its own custom scheme (`tenant.json` `urlScheme`; Android and iOS must use the same one,
  `tenant_builder validate` enforces it).

### Backend setup per white-label store (central admin)
Central admin → **Plans → Registered Users** (`/admin/plans/registered-users`) → **Edit** the store → tab **Mobile App**
(permission `plans.tenants.manage`; the store owner cannot edit it). Stored in the central table `tenant_mobile_apps`
(migration `2026_11_05_000001_create_tenant_mobile_apps_table`, model `App\Models\TenantMobileApp`). All fields optional:

| Field | From | Rule |
|---|---|---|
| Android package (applicationId) | `tenant.json` `android.applicationId` | `com.brand.shop` form; not the platform package; needs fingerprints |
| Android SHA-256 certificate fingerprints | Play Console → App integrity: **app signing key** (+ upload key) | one per line, 64 hex, colons optional; saved as `AA:BB:…` uppercase |
| Apple Team ID + iOS bundle id | `ios.teamId` + `ios.bundleId` | both or neither; published as `<TeamID>.<BundleID>` |
| URL scheme | `android.urlScheme` / `ios.urlScheme` | `^[a-z][a-z0-9+.-]*$`, 2-41 chars, unique across stores, not the platform scheme or a reserved one (`https`, `intent`, …) |
| Google Play URL / App Store URL | store listings after release | `https://` URLs |

Result on every domain of the store (`store.host` and each `extraLinkHosts` entry must be a domain of that tenant):
`assetlinks.json` has one statement for the store app and one for the platform app; AASA `applinks.details[0].appIDs`
and `webcredentials.apps` = `[<store app>, <platform app>]`, components `/` → `/*`.

### Payment return / "Open in app" scheme
- The app sends `X-App-Scheme: <its scheme>` on every API call (`AppConfig.urlScheme`, dart-define `APP_URL_SCHEME`
  written by `tenant_builder` from `tenant.json`; default `storeapp` for the platform app / plain `flutter run`).
- `POST /orders/{uuid}/pay` puts that scheme into the gateway return URLs as `app_scheme=` (only when it is not the
  platform scheme); `/payments/{tenant}/{gateway}/success|cancel` redirect to `<scheme>://payment/result?...`.
- Security rule (no open redirect): a scheme from the header or the return URL is used only when it equals the platform
  scheme (`MOBILE_DEEP_LINK_SCHEME`) or **this** store's configured scheme; anything else falls back to the platform
  scheme. The platform app on a store with its own app therefore still gets `storeapp://`.

## DNS
Wildcard `*.platform.example.com` must resolve to the app server with a valid wildcard TLS cert, and serve the two
well-known files over HTTPS with no redirect, `application/json`, publicly (no auth).

## Signing fingerprints (Android)
- Debug: `keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android` (dev only).
- Release with Play App Signing: use the "App signing key certificate" SHA-256 from Play Console > App integrity (NOT your
  upload key). List the upload key too if you sideload upload-signed builds. Platform app: all in `MOBILE_ANDROID_SHA256`;
  a white-label app: the store's **Mobile App** tab (above).
- Release builds are signed only with the tenant's upload key (`ANDROID_SIGNING_TENANT` + `ANDROID_KEYSTORE_*` env,
  `android/keystores/<id>.properties`, or `android/key.properties` for `platform`); without it the release build fails.
  Each white-label app has its own package id and upload key, hence one assetlinks entry per store app (Mobile App tab).

## Apple Team ID
Apple Developer > Membership. Platform app: `MOBILE_IOS_APP_ID = <TeamID>.<BundleID>`; a white-label app: Team ID + bundle id
in the store's Mobile App tab (the tenant's own team). Enable the Associated Domains capability on the App ID.
AASA must be reachable by Apple's CDN (can lag; test on a real device, not only the simulator).

## Custom domain scalability analysis
- Android `autoVerify` and iOS `applinks:` entitlements are fixed at build time per host. Wildcards only work for subdomains
  of a domain we control. A tenant custom domain (`shop.brand.com`) would need its own `<data android:host>` and
  `applinks:` entry baked into the app, plus the well-known files served there. We do host those files on every tenant
  domain (same routes), but the host list in the binary cannot grow per tenant without a new release for every new domain.
  Impractical for a multi-tenant app with dynamic domains.
- Recommended: (1) verified links on platform subdomains only; (2) custom domains open the `/app` web landing, which hands
  off via the custom scheme `scheme://<host>/<path>` (works without verification, may show a chooser/prompt); (3) manual
  store-code / domain entry in the app as the recovery path.
- Optional: per-tenant white-label build (own package id, entitlements, domains) for tenants who need verified links.
- Optional: Play Install Referrer (`com.android.installreferrer`) to carry `?store=` through a Play install so first launch
  preselects the store. Not implemented; add only if install-attribution is needed (iOS has no equivalent).

## Verification checklist (to do once inputs exist; none done yet)
1. `curl -i https://<store>.<platform>/.well-known/assetlinks.json` and the AASA URL: 200, JSON, expected ids.
2. Android: `adb shell pm get-app-links <package>` shows `verified`; `adb shell am start -a android.intent.action.VIEW -d https://<store>.<platform>/products/x`.
3. iOS: open the link from Notes/Messages on a device; check Settings > Developer > Universal Links diagnostics.
