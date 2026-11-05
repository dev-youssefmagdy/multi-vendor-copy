# White-label mobile apps: architecture

One Flutter codebase (`application/`) → one separately installable, separately published app per store
(tenant). Build commands: [WHITE_LABEL_BUILDS.md](WHITE_LABEL_BUILDS.md). Store/account runbook and phase
plan: [application/PUBLISH_TO_STORES.md](../../application/PUBLISH_TO_STORES.md). Tenant schema:
[application/tenants/README.md](../../application/tenants/README.md).

Status (2026-11-05): Android, iOS and shared parts implemented (PUBLISH_TO_STORES.md phases 0–6, 9, docs of 10).
The iOS path is verified on Linux only (generator, workspace snapshot, dry runs, IPA verifier on synthetic IPAs);
no real `flutter build ipa` / signing / TestFlight upload has run yet. Backend per-tenant link files and return scheme
(phase 8) are done (feature tests only). Push (phase 7) is not done.

## 1. Audit (baseline before this work)

| Area | Found |
|---|---|
| Flutter | 3.47.1 / Dart 3.13 (pubspec `sdk: ^3.13.1`), Riverpod 3, go_router 18, dio. Entry point `lib/main.dart` only. |
| Config | `lib/core/config/env.dart`: `String.fromEnvironment` (`PLATFORM_API_BASE`, `APP_VERSION`) → `AppConfig` provider. |
| Tenant resolution | Runtime "store browser": `TenantController.start()` → deep link host → `PLATFORM_API_BASE` host (if it is a store) → stored active store → store-entry screen (URL or store code, recents). Users can change store. |
| Backend contract | One central API `PLATFORM_API_BASE/api/mobile/v1`; store selected with `X-Store` / `X-Tenant` headers in `lib/core/network/api_client.dart` from `ApiSession.tenant`; `/resolve` maps host/code → tenant. **Unchanged** by white-label. |
| Branding | From `GET /bootstrap` at runtime (name, logo, colors, theme). Native: one name "Store App", neutral launcher icon and splash mark. |
| Android | Kotlin DSL, AGP 9.1, `namespace com.example.application`. Identity in `gradle.properties` (`app.applicationId=com.yourcompany.storeapp`, `app.platformDomain`, `app.urlScheme=storeapp`) as manifest placeholders. App Links `*.platformDomain`, custom scheme. **Release signed with the debug key.** No Firebase. |
| iOS | No Podfile (SPM). Identity in `ios/Flutter/AppIdentity.xcconfig`. `CFBundleDisplayName` hardcoded. Missing camera/photo purpose strings although `image_picker` is used. |
| Push / payments | Push off (`NoPushTokenSource`). Payment return via `<scheme>://payment/result` deep link. |
| CI | None. |

## 2. What changed

### Tenant registry (source of truth)
`application/tenants/index.json` + `tenants/<id>/tenant.json` (+ assets). `platform` = today's generic app with
today's placeholder ids; `demo` = sample white-label store with placeholder ids for isolation testing.

### Dart: build-time binding (no runtime switching)
- `Env` reads `TENANT_ID`, `TENANT_HOST`, `TENANT_NAME`, `TENANT_LINK_HOSTS`, `APP_URL_SCHEME` (from
  `build/tenant_config/<id>.json`, written by the tool, passed with `--dart-define-from-file`).
- `AppConfig.tenant` (`TenantBinding`): immutable, `isWhiteLabel = host.isNotEmpty`.
- `main.dart` fails fast (error screen, app does not start) when the binding is inconsistent, including
  Flutter's `appFlavor` ≠ `TENANT_ID` (a `demo` flavor built without demo's defines).
- `TenantController` (white-label): `start()` always enters `TENANT_HOST` (stored store and
  `PLATFORM_API_BASE` probing ignored); never `needsStore`; `changeStore`, `submitInput`, `selectRecent` are
  no-ops; `retry` re-enters the same host; links to own host / `TENANT_LINK_HOSTS` navigate; links (or
  `?store=` handoffs) to any other store open in the browser via `externalLinkOpenerProvider`
  (`url_launcher`) and never switch the store.
- UI: `/store-entry` route not registered; invalid/suspended screens show **Retry** instead of
  **Change store**; offline/maintenance/update screens hide **Change store**; splash shows `TENANT_NAME`
  before the store's bootstrap is cached.
- Platform build (`TENANT_HOST` empty): behavior identical to before.
- Every API request carries `X-App-Scheme: <APP_URL_SCHEME>` (default `storeapp`) so the backend returns gateway
  payments to the app that started them (validated server-side, see DEEPLINKS.md).

### Android
- `android/app/build.gradle.kts` reads `tenants/index.json` + each enabled `tenant.json` at configuration
  time and creates one product flavor per tenant (dimension `tenant`, flavor name = tenant id):
  explicit `applicationId`, `resValue string app_name`, placeholders `appLinkHost` (`*.<platformDomain>` for
  `platform`, `store.host` for a store) and `urlScheme`. `namespace` unchanged.
- `AndroidManifest.xml`: App Links host = `${appLinkHost}`; `<queries>` for https VIEW (url_launcher).
  `strings.xml` no longer defines `app_name` (resValue does).
- Generated, gitignored `android/app/src/<id>/`: launcher icons (all densities), optional adaptive icon,
  extra App Links hosts manifest, marker `.tenant_builder.json`. `platform` has none (keeps `main/` icons).
- Signing: release uses only the tenant's upload key (env with `ANDROID_SIGNING_TENANT=<id>`,
  `android/keystores/<id>.properties`, or `android/key.properties` for `platform`). Without it, the
  `pre<Flavor>ReleaseBuild` task fails. Debug/profile keep the debug key.
- Gradle guards: store flavors refuse to build without matching `TENANT_ID`/`TENANT_HOST` dart-defines, and
  without generated icons.

### iOS
Shared project (one-time): `Info.plist`: `CFBundleDisplayName = $(APP_DISPLAY_NAME)`, `NSCameraUsageDescription`,
`NSPhotoLibraryUsageDescription`, `ITSAppUsesNonExemptEncryption=false`. `AppIdentity.xcconfig`:
`APP_DISPLAY_NAME = Store App`. **No `project.pbxproj` change**, no CocoaPods (SPM), no per-tenant schemes,
builds without `--flavor`. The shared `ios/` tree always stays the `platform` app.

Per tenant (`tool/tenant_builder/src/platforms/ios*.dart`): `generate` writes an overlay in
`build/workspaces/<id>/overlay/`; `build`/`run` sync `build/workspaces/<id>/app/` = snapshot of the project
(`git ls-files`, without `tenants/`, `android/`, `dist/`, `build/`) + overlay, and run Flutter there:
- `AppIdentity.xcconfig`: `APP_BUNDLE_ID`, `APP_DISPLAY_NAME`, `APP_ASSOCIATED_DOMAIN`, `APP_URL_SCHEME`, `DEVELOPMENT_TEAM`.
- `TenantSigning.xcconfig` (+ a `Release.xcconfig` that includes it): `CODE_SIGN_STYLE = Manual`,
  `Apple Distribution`, profile `match AppStore <bundleId>`.
- `Runner.entitlements`: literal Universal Links domains (store host + custom domains), `aps-environment` only with push.
- AppIcon set rendered from the tenant icon; `ExportOptions.plist` (`app-store-connect`, manual signing).
- Signing: fastlane match **read-only** from the tenant's own storage into a temporary keychain (CI).
- Upload: fastlane pilot to the tenant's TestFlight with the tenant's App Store Connect API key; no
  review submission or release from tooling.

### Fastlane
One canonical `application/fastlane/Fastfile` (+ `application/Gemfile`), called only through `tenant_builder`:
`ios latest_build_number | match_readonly | upload_testflight`, `android latest_version_code | upload_testing`.

### Tool + CI
`application/tool/tenant_builder` (Dart CLI: list/create/validate/generate/run/build/verify/upload) and
`.github/workflows/android-tenant-release.yml`, `.github/workflows/ios-tenant-release.yml`.

## 3. Native identity model

| | Android | iOS |
|---|---|---|
| Selection | `--flavor <id>` | Runner scheme, no flavor; snapshot workspace `build/workspaces/<id>/app` |
| App id | flavor `applicationId` | `APP_BUNDLE_ID` in the workspace `AppIdentity.xcconfig` |
| Name | `resValue app_name` | `APP_DISPLAY_NAME` |
| Icon | `src/<id>/res/mipmap-*` | AppIcon set in the workspace |
| Links | `appLinkHost` + generated extra hosts | literal `applinks:` entries in the workspace `Runner.entitlements` |
| Scheme | `urlScheme` placeholder | `APP_URL_SCHEME` |
| Signing | tenant upload key | tenant's match profile, manual signing via xcconfig |
| Build number | Play latest + 1 | TestFlight latest + 1 |
| Dart binding | `--dart-define-from-file=build/tenant_config/<id>.json` | same file |

Assets: per-tenant **native** assets only (icons). No tenant images are added to Flutter `assets/`, so no
tenant's branding is bundled into another tenant's binary; `tenant_builder verify` checks that no other
tenant's package id, hosts or icon are inside an artifact.

## 4. Integrations per tenant

| Integration | Tenant-specific? | Status |
|---|---|---|
| Package / bundle id | yes | done |
| App Links / Universal Links | yes (host) | done; backend serves the store app + platform app per domain (phase 8, central admin → store → Mobile App) |
| Custom URL scheme (payment return) | yes (unique) | done; app sends `X-App-Scheme`, backend redirects to it when it is the platform's or the store's scheme (phase 8) |
| Firebase / FCM | yes (one app per id) | Not used yet (phase 7) |
| API / tenancy | no (central API + `X-Tenant`) | unchanged |
| Signing | yes (tenant upload key / tenant Apple team) | done (iOS untested without credentials) |

## 5. Risks and limitations

- `platform.example.com`, `com.yourcompany.storeapp` and all `demo` values are placeholders; release
  validation rejects them.
- A white-label app's App Links / Universal Links verify and its payment return works only after the store's
  **Mobile App** settings are filled in the central admin (package + Play app-signing SHA-256, Team ID + bundle id,
  scheme) and every link host is a registered domain of that store. None of this has been verified on a device.
- Release build numbers come from the store (fastlane); the iOS lookup uses the most recently uploaded build,
  which equals the highest while numbers only grow (the tool enforces that).
- Native splash keeps the neutral mark (`branding.splash` not wired).
- Store policy: Apple 4.2.6 / 4.3 and Google Play repetitive-content rules apply (PUBLISH_TO_STORES.md §10).
  A build is not an approval; each tenant must publish from its own developer account.
- Shorebird (code push) is not integrated. Future: one Shorebird app per flavor, `shorebird release android
  --flavor <id> -- --dart-define-from-file=build/tenant_config/<id>.json` with the exact store-release args;
  native/icon/id changes still need a store release.
