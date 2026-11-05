# Publishing Tenant Apps to Google Play and the App Store

One Flutter codebase → one separately branded, separately published app per store (tenant).

> **Status (2026-11-05):** Phases **0–6, 8, 9** and the docs of 10 are **built** — see
> [docs/mobile/WHITE_LABEL_ARCHITECTURE.md](../docs/mobile/WHITE_LABEL_ARCHITECTURE.md),
> [docs/mobile/WHITE_LABEL_BUILDS.md](../docs/mobile/WHITE_LABEL_BUILDS.md) and
> [docs/mobile/DEEPLINKS.md](../docs/mobile/DEEPLINKS.md). **Not built:** phase 7 (push).
> **Verified only by automated tests** (Flutter unit/widget + `tenant_builder` tests, Laravel feature tests) and Linux
> dry runs. **Not done yet:** no install/smoke test on a real device, no real signed iOS build (needs macOS + a
> tenant's Apple credentials; the IPA verifier ran on synthetic IPAs only), no run of the GitHub workflows, no Play
> internal-track or TestFlight upload, no App Links / Universal Links verification by Google/Apple, and the phase 10
> acceptance test. All ids in `tenants/platform` and `tenants/demo` are placeholders. Where the build differs from
> the original plan, the "As built" notes below say so. Specs this is based on:
> [flutter_white_label_flavors_implementation_prompt.md](flutter_white_label_flavors_implementation_prompt.md),
> [ios_white_label_release_implementation.md](ios_white_label_release_implementation.md).

**Contents**
1. [How it works and what exists today](#1-how-it-works-and-what-exists-today)
2. [Implementation plan](#2-implementation-plan)
3. [Tenant configuration](#3-tenant-configuration)
4. [Who owns what](#4-who-owns-what)
5. [One-time setup per tenant: Google Play](#5-one-time-setup-per-tenant-google-play)
6. [One-time setup per tenant: Apple App Store](#6-one-time-setup-per-tenant-apple-app-store)
7. [First release of a tenant](#7-first-release-of-a-tenant)
8. [Shipping updates (one tenant or all tenants)](#8-shipping-updates-one-tenant-or-all-tenants)
9. [Store listing checklists](#9-store-listing-checklists)
10. [Store policy risk (read before selling this)](#10-store-policy-risk-read-before-selling-this)
11. [Secrets reference](#11-secrets-reference)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. How it works and what exists today

```text
              shared Flutter code (application/lib)
                              │
   tenants/<id>/tenant.json ──┤   (name, package id, bundle id, store domain, icon, scheme)
                              ▼
                       tenant-builder
            validate → generate native identity → build → verify
                 │                                     │
        Android flavor <id>                  iOS: Runner scheme + per-build
        (applicationId, label, icon)         AppIdentity.xcconfig (bundle id, name, icon)
                 │                                     │
             signed .aab                           signed .ipa
                 │                                     │
   tenant's Play Console (internal track)   tenant's App Store Connect (TestFlight)
                 │                                     │
        tenant promotes to production        tenant submits for review / releases
```

At runtime nothing changes architecturally: every app still talks to the central platform API
(`PLATFORM_API_BASE/api/mobile/v1`) and sends `X-Store` / `X-Tenant` headers (plus `X-App-Scheme`, so payment
returns reach the right app). The only difference is that a
white-label binary is **locked to one store domain at build time** instead of asking the user which store to open.

### Audit of the app before this work (baseline)

| Area | Before | What white-label needs |
|---|---|---|
| Tenant selection | Runtime: deep link → `PLATFORM_API_BASE` host → stored store → store-entry screen ([tenant_controller.dart](lib/features/startup/tenant_controller.dart)). Users can switch stores. | Build-time `TENANT_HOST`; always open that store; no store-entry, recents or "change store"; links to other stores open in the browser. |
| Branding (colors, logo, theme, sections) | From `GET /bootstrap` at runtime | Unchanged. Only native name, launcher icon and splash become per-tenant. |
| API / tenancy contract | `X-Store` + `X-Tenant` headers on one central API ([api_client.dart](lib/core/network/api_client.dart)) | Unchanged. |
| Android identity | One place: `app.applicationId`, `app.platformDomain`, `app.urlScheme` in [android/gradle.properties](android/gradle.properties) (placeholders `com.yourcompany.storeapp`) | Per-tenant product flavor. |
| Android release signing | **Signed with the debug key** ([build.gradle.kts](android/app/build.gradle.kts)) | Per-tenant upload keystore from CI secrets. Play rejects debug-signed bundles. |
| iOS identity | One place: [ios/Flutter/AppIdentity.xcconfig](ios/Flutter/AppIdentity.xcconfig) (`APP_BUNDLE_ID`, `APP_ASSOCIATED_DOMAIN`, `APP_URL_SCHEME`); `CFBundleDisplayName` hardcoded `Store App` | Generated per build; display name and icon from the xcconfig too. |
| iOS dependencies | No Podfile (Swift Package Manager via Flutter 3.41) | Nothing to do; keep SPM. |
| iOS privacy strings | **Missing** `NSPhotoLibraryUsageDescription` / `NSCameraUsageDescription` although `image_picker` is used | Add before any upload (App Store Connect rejects the build otherwise). Add `ITSAppUsesNonExemptEncryption=false` too. |
| Push | Off (`PUSH_ENABLED`, no Firebase) | Stays off. When turned on: one Firebase app per package/bundle id (§2 phase 7). |
| Deep links / payment return | App Links + Universal Links on `*.platformDomain`; custom scheme `storeapp://` for payment return | Per-tenant scheme (two apps sharing `storeapp://` on one phone would steal each other's payment returns) and per-tenant link files on the backend. |
| Backend link files | [WellKnownController](../app/Http/Controllers/Mobile/WellKnownController.php) serves **one** package / one iOS app id from `.env` | Serve the requesting tenant's own app ids (plus the generic platform app). |
| Account deletion | Exists in app (required by both stores) | Keep. |
| CI | None | GitHub Actions: Linux for Android, macOS for iOS. |

---

## 2. Implementation plan

Each phase is a separate, reviewable PR. Nothing is uploaded to a store automatically at any phase; uploads go
to Play **internal testing** / **TestFlight** only, and production release stays a manual action by the tenant.

### Phase 0 — Release blockers in the current app
- Add iOS purpose strings (`NSPhotoLibraryUsageDescription`, `NSCameraUsageDescription`) and
  `ITSAppUsesNonExemptEncryption = false` to `ios/Runner/Info.plist`.
- Replace the debug release `signingConfig` with one read from environment / `android/key.properties`
  (gitignored). Fail the release build if the keystore is missing; never fall back to debug.
- Add an `ios/Runner/PrivacyInfo.xcprivacy` if the app's own code uses required-reason APIs (plugins ship their own).
- **As built:** purpose strings + `ITSAppUsesNonExemptEncryption` added; `CFBundleDisplayName = $(APP_DISPLAY_NAME)`
  with `APP_DISPLAY_NAME = Store App` in `AppIdentity.xcconfig`. Release signing per tenant (see Phase 3).
  **No `PrivacyInfo.xcprivacy`:** the app's own native code (`AppDelegate.swift`, `SceneDelegate.swift`) uses no
  required-reason API; Flutter and the plugins (`shared_preferences`, `image_picker`, …) ship their own manifests.
  Add one (with Runner target membership) only if app-owned native code starts using such an API.

### Phase 1 — Tenant registry and schema
- `application/tenants/index.json` (enabled/disabled list) and `tenants/<id>/tenant.json` (§3).
- `tenants/_template/` and `tenants/platform/` = the current generic store-browser app with today's placeholder ids,
  so the existing behavior remains a buildable "tenant".
- Tenant id rule: `^[a-z][a-z0-9]{2,30}$` (valid as Gradle flavor, folder and CLI arg).
- **As built:** also `tenants/demo/` (sample white-label store, placeholder ids) and `tenants/README.md`.
  `index.json` carries `platformDomain` (was `app.platformDomain` in gradle.properties). `tenant.json` has
  `"type": "platform" | "store"` (see §3). Ids starting with `test` and Gradle names (`main`, `debug`, …) are reserved.

### Phase 2 — Dart: lock a binary to one store
- [env.dart](lib/core/config/env.dart): add `TENANT_ID`, `TENANT_HOST` (`String.fromEnvironment`); `isWhiteLabel = TENANT_HOST.isNotEmpty`.
- `tenant-builder` writes `build/tenant_config/<id>.json` and passes it with `--dart-define-from-file`.
- `TenantController.start()`: when white-label, always `_enter(host: Env.tenantHost)`; never go to `needsStore`.
  Invalid/suspended screens show no "change store" button. `handleDeepLink` for another host → open in browser.
- Hide store-entry route, recents and "change store" in white-label builds ([router.dart](lib/app/router.dart), [screens.dart](lib/features/startup/screens.dart)).
- Tests: white-label never shows store entry; a link to another store does not switch; platform build behaves as today.
- **As built:** define keys `TENANT_ID`, `TENANT_HOST`, `TENANT_NAME`, `TENANT_LINK_HOSTS` (+ `APP_VERSION`,
  `APP_URL_SCHEME` (Phase 8), optional `PLATFORM_API_BASE`) in `AppConfig.tenant` (`TenantBinding`). Links to
  `store.extraLinkHosts` stay in the app. `main.dart` refuses to start when `appFlavor` and `TENANT_ID` disagree. External links use `url_launcher`.

### Phase 3 — Android flavors
- `build.gradle.kts` reads `tenants/index.json` + each `tenant.json` and declares one product flavor per enabled
  tenant (dimension `tenant`) with explicit `applicationId`, `resValue("string","app_name",…)` and
  `manifestPlaceholders` (`platformDomain`, `tenantHost`, `urlScheme`). `namespace` stays `com.example.application`.
- App Links intent filter: tenant's own host(s) (`tenantHost` + optional custom domain) instead of `*.platformDomain`.
- Per-flavor icons generated into `android/app/src/<id>/res/` (gitignored, created by `tenant-builder generate`
  using `flutter_launcher_icons` with a per-tenant config). Adaptive icon foreground/background supported.
- Release signing per tenant from env: `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS`, `ANDROID_KEY_PASSWORD`.
- **As built (deviations):**
  - Manifest placeholders are `appLinkHost` (`*.<platformDomain>` for `platform`, `store.host` for a store) and
    `urlScheme`; `extraLinkHosts` go into a generated `android/app/src/<id>/AndroidManifest.xml`.
  - Icons are generated by `tenant_builder` itself (Dart `image` package, deterministic), not `flutter_launcher_icons`.
  - Gradle reads a keystore **file**: CI decodes `ANDROID_KEYSTORE_BASE64` to `ANDROID_KEYSTORE_PATH`. Env credentials
    apply only when `ANDROID_SIGNING_TENANT=<id>` matches the flavor. Locally: `android/keystores/<id>.properties`
    (or `android/key.properties` for `platform`). Missing key → release build fails.
  - Store flavors fail in Gradle when built without their `build/tenant_config/<id>.json` defines.

### Phase 4 — iOS: one scheme, per-build identity
Per the iOS spec, no per-tenant Xcode schemes (they don't scale and need `project.pbxproj` edits).
- Keep the existing `Runner` scheme and `Debug/Release/Profile` configs. iOS builds run **without** `--flavor`.
- `tenant-builder` builds each iOS tenant in an isolated `git worktree` under `build/workspaces/<id>/` and writes there:
  - `ios/Flutter/AppIdentity.xcconfig`: `APP_BUNDLE_ID`, `APP_DISPLAY_NAME`, `APP_ASSOCIATED_DOMAIN`, `APP_URL_SCHEME`, `DEVELOPMENT_TEAM`.
  - `ios/Runner/Assets.xcassets/AppIcon.appiconset` from the tenant icon.
  - `GoogleService-Info.plist` only when Firebase is enabled.
- One-time change in the shared project: `Info.plist` `CFBundleDisplayName = $(APP_DISPLAY_NAME)`; Runner target
  `DEVELOPMENT_TEAM = $(DEVELOPMENT_TEAM)`. The working tree's `AppIdentity.xcconfig` stays the `platform` tenant.
- **As built (deviations):**
  - The workspace is a **file snapshot**, not a `git worktree`: `build/workspaces/<id>/app/` = the project's files as
    `git ls-files --cached --others --exclude-standard` lists them (so uncommitted local changes are built, like
    the Android build), minus `tenants/`, `android/`, `dist/`, `build/`, plus the tenant overlay. A worktree would
    silently build `HEAD` only and check out the whole Laravel repo. Files are rewritten only when they differ, so
    Xcode/Flutter caches survive; an OS file lock (`build/workspaces/<id>/.lock`) stops two builds of one tenant
    sharing it. `build-info.json` records the git SHA and a `gitDirty` flag.
  - `generate --platform ios` writes the tenant files to `build/workspaces/<id>/overlay/` (reviewable, idempotent,
    `--check`-able); `build`/`run` lay them over the snapshot:
    `ios/Flutter/AppIdentity.xcconfig` (`APP_BUNDLE_ID`, `APP_DISPLAY_NAME`, `APP_ASSOCIATED_DOMAIN`, `APP_URL_SCHEME`,
    `DEVELOPMENT_TEAM`), `ios/Flutter/TenantSigning.xcconfig` + `ios/Flutter/Release.xcconfig` (manual signing with the
    match profile `match AppStore <bundleId>`, `Apple Distribution`; Release/Profile only), `ios/Runner/Runner.entitlements`
    with **literal** `applinks:`/`webcredentials:` entries for `store.host` + every `extraLinkHosts` entry (`*.<platformDomain>`
    for `platform`), the AppIcon set (all 19 slots rendered from `branding.appIcon`; `platform` keeps the committed set),
    `ExportOptions.plist` (`app-store-connect`, manual, `manageAppVersionAndBuildNumber=false`) and, only with push,
    `GoogleService-Info.plist`.
  - **No `project.pbxproj` change at all.** The Runner target sets neither `DEVELOPMENT_TEAM`, `CODE_SIGN_STYLE` nor a
    profile, so the xcconfig values apply; target-level xcconfig overrides the project-level
    `CODE_SIGN_IDENTITY[sdk=iphoneos*] = iPhone Developer`. (Phase 7 will need one pbxproj change: add
    `GoogleService-Info.plist` to the Runner target's resources, otherwise Xcode does not bundle it.)
  - The `generate`/`build` commands refuse to run when the shared `ios/Flutter/AppIdentity.xcconfig` contains a store
    tenant's bundle id or scheme (stale values).

### Phase 5 — `tenant-builder` CLI (Dart, `application/tool/tenant_builder/`)
Run from `application/` as `dart run tool/tenant_builder/bin/tenant_builder.dart …` (alias it as `tenant-builder`).

| Command | Does |
|---|---|
| `list` | Registered tenants and enabled platforms |
| `create --tenant <id>` | Copy `_template`; refuses to overwrite |
| `validate --tenant <id> [--platform android\|ios]` | Schema, unique package/bundle ids, URLs, colors, icon ≥1024×1024 with no alpha (iOS), secret refs resolvable when `--release` |
| `generate --tenant <id>\|--all` | Dart define file, Android flavor resources, iOS workspace files. Idempotent. |
| `run --tenant <id> --platform android\|ios` | Local run |
| `build --tenant <id>\|--all --platform android --artifact aab\|apk` | `flutter build appbundle --release --flavor <id> --dart-define-from-file=…` |
| `build --tenant <id>\|--all --platform ios --artifact ipa` | macOS only; isolated workspace; `flutter build ipa --release --export-options-plist=…` |
| `verify --tenant <id>` | Reads the built AAB/IPA: package/bundle id, label/display name, version, icon, scheme, entitlements |
| `upload --tenant <id>\|--all --platform android --track internal` | Fastlane `supply` to Play internal testing |
| `upload --tenant <id>\|--all --platform ios --destination testflight` | Fastlane `pilot` to TestFlight |

**As built:** `tool/tenant_builder/bin/tenant_builder.dart` (code in `tool/tenant_builder/src/`, platform targets in
`src/platforms/`: `android.dart`, `ios.dart` + `ios_native.dart` / `ios_workspace.dart` / `ios_verify.dart` /
`ios_signing.dart` / `plist.dart`). All commands work for both platforms. Every fastlane call goes through lanes of the
single `application/fastlane/Fastfile` via `bundle exec fastlane` (`application/Gemfile`; `FASTLANE_BIN` overrides):
`android upload_testing` (tracks internal/alpha/beta only, `--release-status draft|completed`),
`android latest_version_code`, `ios latest_build_number`, `ios match_readonly`, `ios upload_testflight`.
iOS: `build` is macOS-only (clear error elsewhere; `--dry-run` works on Linux) and `--mode release` only;
`verify --platform ios` parses the IPA in pure Dart (works on Linux) and adds `codesign --verify --deep --strict` on
macOS; `upload --platform ios --destination testflight` re-verifies the IPA and needs `ios.appStoreConnectAppId`.
`upload --all` continues after a tenant fails and prints a summary.
Extra: `generate --check`, `build --dry-run`, `build --mode debug|profile` (Android), `--build-name/--build-number`.

Artifacts: `dist/<id>/android/<id>-<version>+<build>.aab`, `dist/<id>/ios/<id>-<version>+<build>.ipa`, plus
`build-info.json` (tenant, ids, version, build number, git SHA, time; no secrets). `--all` builds sequentially and
prints a per-tenant summary (ok / failed / skipped). There is **no** `submit` or `release` command by design.

### Phase 6 — Versions and build numbers
- Marketing version (`1.4.0`) comes from `pubspec.yaml` and is the same for every tenant built from one commit.
- Build number is **per tenant**: before building, fastlane reads the latest one from the tenant's store
  (`google_play_track_version_codes` / `latest_testflight_build_number`) and uses `+1`. If the lookup fails, the
  build stops unless `--build-number` is given explicitly.
- **As built:** release builds of both platforms (`buildNumberFor` in `src/cli.dart`). Android = highest versionCode
  over the internal/alpha/beta/production tracks (fails if no track is readable). iOS = latest uploaded build of the
  bundle id across versions (`latest_testflight_build_number`, no `version:`), `0` when the app has no build yet.
  An explicit `--build-number` must still be **higher** than the store's latest when the lookup works; if the lookup
  fails, the override is used with a warning. Debug/profile builds and `--dry-run` use `pubspec.yaml` (or the
  override). CI concurrency per tenant (`release-<platform>-<id>`) plus the workspace lock prevent two jobs
  allocating the same number. A brand-new Play app (first upload is manual) needs `--build-number`.

### Phase 7 — Push notifications (only when push is switched on)
- One Firebase project for the platform with one Android app + one iOS app registered **per tenant** (or one
  project per tenant if a tenant needs isolation). Files go in `tenants/<id>/android/google-services.json` and
  `tenants/<id>/ios/GoogleService-Info.plist`; the validator checks their package/bundle id matches.
- APNs auth key (.p8) from the **tenant's** Apple account uploaded to that Firebase iOS app.
- Token registration already goes through `X-Tenant`; the backend must send through the matching Firebase app.

### Phase 8 — Backend changes (Laravel)
- New per-tenant "mobile app" settings (central admin): Android package, SHA-256 fingerprints, iOS `<TeamID>.<BundleID>`,
  URL scheme, Play Store URL, App Store URL.
- `WellKnownController`: on a tenant domain, return that tenant's app ids **plus** the generic platform app.
- `AppLandingController` (`/app` page) and payment success/cancel redirects: use the tenant's scheme and store URLs,
  falling back to `config/mobile.php`. (Payment redirect touches checkout: follow [CLAUDE.md](../CLAUDE.md) rules.)
- Feature test per tenant for both well-known files.
- **As built:**
  - Settings: central table `tenant_mobile_apps` (migration `2026_11_05_000001_create_tenant_mobile_apps_table`, model
    `App\Models\TenantMobileApp`, `Tenant::mobileApp()`), one optional row per tenant. Edited only in the central
    admin: **Plans → Registered Users → Edit → tab "Mobile App"** (`PUT /admin/plans/registered-users/{tenant}/mobile-app`,
    permission `plans.tenants.manage`). Fields: Android package, SHA-256 fingerprints (several, one per line, normalised
    to `AA:BB:…`), Apple Team ID + iOS bundle id (published as `<TeamID>.<BundleID>`), URL scheme (unique across
    stores, never the platform scheme), Play Store URL, App Store URL. Rules: [DEEPLINKS.md](../docs/mobile/DEEPLINKS.md).
  - `WellKnownController` resolves the request host through the `domains` table (subdomain or custom domain): store
    app + platform app there; platform app only on the central domain / unknown hosts.
  - Return scheme: the app sends `X-App-Scheme` (dart-define `APP_URL_SCHEME`, written by `tenant_builder` from
    `tenant.json`; `validate` now requires `android.urlScheme == ios.urlScheme`). `POST /orders/{uuid}/pay` carries it
    into the gateway return URLs (`app_scheme=`); success/cancel redirect to it. Accepted only when it equals the
    platform scheme or the store's configured scheme, else the platform scheme (no open redirect). Only the redirect
    scheme changed; no order, price or payment-confirmation logic.
  - `/app` landing: the store's scheme and store URLs when it has its own app (fallback `config/mobile.php`), plus a
    secondary link for the platform app. (Deviation: a browser visit is never "from the app", so the landing prefers
    the store's own app, matching its install buttons.)
  - Tests: `tests/Feature/Mobile/TenantMobileAppTest.php`. Run `php artisan migrate` (central) on deploy.

### Phase 9 — CI (`.github/workflows/` at repo root, `working-directory: application`)
- `android-tenant-release.yml` (ubuntu): inputs `tenant` or `all`, `action = build | upload_internal`.
  format → analyze → test → validate → build → verify → upload artifact → optional internal-track upload.
- `ios-tenant-release.yml` (macOS, pinned Xcode): inputs `tenant` or `all`, `action = build | upload_testflight`.
  Temporary keychain, `match` read-only, build, verify, upload, cleanup in `if: always()`.
- One GitHub Environment per tenant and platform (`tenant-alpha-android`, `tenant-alpha-ios`) holding only that
  tenant's secrets, with required reviewers. Concurrency group `release-<platform>-<tenant>`.
- **As built:** `android-tenant-release.yml` (Flutter 3.47.1). Format check covers the new tooling only (existing
  `lib/` is not `dart format` clean). Extra input `release_status`. Environment name from `tenant.json`
  `android.secretsEnvironment` (default `tenant-<id>-android`). Repository variable `PLATFORM_API_BASE`. The build
  step now decodes `PLAY_SERVICE_ACCOUNT_JSON_BASE64` first (versionCode lookup); without it, pass `build_number`.
- **As built:** `ios-tenant-release.yml`: inputs `tenant` (id, list or `all`), `action = build | upload_testflight`,
  `build_number`, `marketing_version`. Checks job on ubuntu (format, analyze, test, `validate`/`generate --check` for
  iOS, matrix from `list --json --enabled --platform ios`), then one job per tenant on `macos-26` (repo variable
  `IOS_RUNNER_LABEL` overrides) with Xcode pinned by `XCODE_VERSION` (default `26.2`, repo variable `IOS_XCODE_VERSION`;
  the step fails listing installed versions if missing), checkout of `github.sha`, Ruby 3.3 + `bundler-cache`,
  version log, `validate --release`, temporary keychain with a random name, ssh-agent for `MATCH_GIT_SSH_KEY`,
  `tenant_builder build` (lookup → `match` read-only → `flutter build ipa` → verify), artifact `ios-<id>-<run>`
  (IPA + `build-info.json` only), optional TestFlight upload, job summary, and an `if: always()` cleanup (keychain,
  provisioning profiles, ssh-agent, workspace). `IOS_SIGNING_TENANT` is set to the matrix tenant.

### Phase 10 — Docs and verification
- [docs/mobile/WHITE_LABEL_ARCHITECTURE.md](../docs/mobile/WHITE_LABEL_ARCHITECTURE.md),
  [docs/mobile/WHITE_LABEL_BUILDS.md](../docs/mobile/WHITE_LABEL_BUILDS.md), [tenants/README.md](tenants/README.md);
  [docs/mobile/DEEPLINKS.md](../docs/mobile/DEEPLINKS.md) updated (Android, iOS and backend per-tenant setup).
- Acceptance (**not done yet**): two sample tenants install side by side on one Android phone and one iPhone, each
  shows its own name/icon/store, payment return opens the right app, and neither contains the other's ids or icon.

---

## 3. Tenant configuration

`application/tenants/<id>/` (public, committed). **No secrets here, only names of secrets.**

```text
tenants/alpha/
├── tenant.json
├── assets/
│   ├── app_icon.png              1024×1024 PNG, no transparency (iOS rejects alpha)
│   ├── app_icon_foreground.png   optional Android adaptive foreground, 1024×1024 with safe zone
│   └── splash.png                optional
├── android/google-services.json  only if push is on
└── ios/GoogleService-Info.plist  only if push is on
```

```json
{
  "schemaVersion": 1,
  "id": "alpha",
  "type": "store",
  "displayName": "Alpha Store",
  "store": {
    "host": "alpha.platform.example.com",
    "extraLinkHosts": ["shop.alpha.com"]
  },
  "android": {
    "enabled": true,
    "applicationId": "com.alphabrand.shop",
    "urlScheme": "alphashop",
    "secretsEnvironment": "tenant-alpha-android"
  },
  "ios": {
    "enabled": true,
    "bundleId": "com.alphabrand.shop",
    "teamId": "ABCDE12345",
    "appStoreConnectAppId": "6740000000",
    "sku": "alpha-shop-ios",
    "urlScheme": "alphashop",
    "secretsEnvironment": "tenant-alpha-ios"
  },
  "branding": {
    "appIcon": "assets/app_icon.png",
    "appIconForeground": "assets/app_icon_foreground.png",
    "adaptiveIconBackground": "#FFFFFF",
    "splash": "assets/splash.png"
  },
  "push": { "enabled": false }
}
```

Rules:
- `applicationId` / `bundleId` are **permanent** once the app is published. Use the tenant's own reverse domain
  (`com.<theirbrand>.<app>`), not ours, because the app belongs to them.
- `store.host` must resolve to an active store through `/resolve`; `validate --release` checks it.
- `urlScheme` must be unique across tenants, and `android.urlScheme` must equal `ios.urlScheme`.
- `type`: `store` (locked to `store.host`) or `platform` (generic store browser, no `store`; at most one).
- `branding.splash` is accepted but not used yet. Full field reference: [tenants/README.md](tenants/README.md).

---

## 4. Who owns what

Both specs (and Apple guideline 4.2.6) point to the same model: **each store owner (tenant) owns their developer
accounts and app listings; we build and upload on their behalf.**

| Item | Owner | Our access |
|---|---|---|
| Google Play developer account ($25 once) | Tenant | Invited user + service account (release permissions on their app only) |
| Apple Developer Program ($99/year) | Tenant | Invited App Store Connect user + API key |
| App listing, screenshots, privacy answers, pricing, countries | Tenant (we can prepare drafts) | Edit if granted |
| Submit for review / release to production | **Tenant decides** | We never do this automatically |
| Android upload keystore | We generate and store per tenant in CI secrets; tenant gets a backup copy | — |
| Android app signing key | Google (Play App Signing) | — |
| iOS distribution certificate and profile | Tenant's Apple team, stored in a per-tenant encrypted `match` storage | Read-only in CI |
| Store domain, store data, support email, privacy policy URL | Tenant | — |

The platform's own generic app (`platform` tenant) is published from **our** accounts like a normal app.

---

## 5. One-time setup per tenant: Google Play

Total time: about 1 hour of work, plus Google's waiting times (account verification can take days; see the
testing rule in step 1).

### Step 1 — Tenant creates the Play developer account
1. Go to <https://play.google.com/console/signup>, sign in with a Google account the **business** controls
   (not a personal Gmail of an employee).
2. Choose **Organization** account if they are a company. Needs a D-U-N-S number, organization website and
   phone. Pay the $25 fee. Complete identity verification.
3. Important difference:
   - **Organization accounts** can publish to production directly after setup.
   - **Personal accounts created after Nov 2023** must run a **closed test with at least 12 testers opted in for
     14 days in a row** before they can apply for production access. Plan for this (see §7.1). Check the current
     rule in the Play Console dashboard; it shows what the account needs.

### Step 2 — Tenant creates the app
Play Console → **Create app**:
- App name: the tenant's store name (max 30 characters).
- Default language, **App** (not game), **Free**.
- Accept the declarations.

The package name is fixed by the first upload (§7.1), so the app record is empty until then.

### Step 3 — Tenant invites us
Play Console → **Users and permissions** → **Invite new users**:
1. Our release operator's email, **app permissions for this app only**: *Release to testing tracks*,
   *Manage testing tracks*, *View app information*, *Edit store listing* (optional).
   Do **not** request *Release to production* unless the tenant explicitly wants us to do production rollouts.
2. A **service account** for CI (step 4) with the same app-level permissions.

### Step 4 — Service account for CI uploads
Done once per tenant by us or the tenant (whoever owns the Google Cloud project; tenant-owned is cleaner):
1. Google Cloud Console → create project `alpha-play-publisher` → enable **Google Play Android Developer API**.
2. IAM → **Service accounts** → create `play-uploader` → **Keys** → add JSON key. Download once.
3. In Play Console → Users and permissions → invite the service account's email (step 3).
4. Store the JSON (base64) in GitHub Environment `tenant-alpha-android` as `PLAY_SERVICE_ACCOUNT_JSON_BASE64`.
   Delete the local copy.

### Step 5 — Upload key
```bash
keytool -genkeypair -v -storetype PKCS12 \
  -keystore alpha-upload.jks -alias upload \
  -keyalg RSA -keysize 4096 -validity 10000 \
  -dname "CN=Alpha Store, O=Alpha Brand, C=EG"
base64 -w0 alpha-upload.jks   # → ANDROID_KEYSTORE_BASE64
```
Save in `tenant-alpha-android`: `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS=upload`,
`ANDROID_KEY_PASSWORD`. Keep an encrypted offline backup and give the tenant a copy. If it is lost, the tenant can
request an upload key reset in Play Console (App integrity), so it is recoverable, but slow.

### Step 6 — Tenant config
`tenant-builder create --tenant alpha`, fill `tenant.json` and icons, then
`tenant-builder validate --tenant alpha --platform android`.

### Step 7 — Backend settings
Central admin → **Plans → Registered Users → Edit** (the store) → tab **Mobile App**: enter **Android package**
(= `android.applicationId`) and **URL scheme** (= `urlScheme`) now. Make sure `store.host` and every `extraLinkHosts`
entry are domains of this store (the well-known files are served per domain). The SHA-256 fingerprints follow after the
first upload (§7.1 step 3), the Play URL after release (§7.1 step 8).

---

## 6. One-time setup per tenant: Apple App Store

Total time: about 1–2 hours of work, plus Apple enrollment (D-U-N-S lookup and enrollment approval can take
1–2 weeks for organizations).

### Step 1 — Tenant enrolls in the Apple Developer Program
1. <https://developer.apple.com/programs/enroll/> with an Apple Account owned by the business, 2FA on.
2. Enroll as **Organization** (needs D-U-N-S number, legal entity name, website). Individual enrollment shows the
   person's name as seller, which most stores don't want.
3. Pay $99/year. In App Store Connect → **Business**, accept the agreements (the Free Apps agreement is enough;
   the app sells physical goods, which do not use Apple in-app purchase).
4. Note the **Team ID** (developer.apple.com → Membership details) → `tenant.json` `ios.teamId`.

### Step 2 — Register the Bundle ID
developer.apple.com → Certificates, Identifiers & Profiles → **Identifiers** → `+` → App IDs → App:
- Description: `Alpha Store`, Bundle ID **Explicit**: exactly `ios.bundleId` (e.g. `com.alphabrand.shop`).
- Capabilities: **Associated Domains** only (plus **Push Notifications** when push is on). Enable nothing else.

### Step 3 — Create the App Store Connect app record
App Store Connect → Apps → `+` → **New App**:
- Platform iOS, Name (max 30 chars, must be unique on the App Store), primary language,
  Bundle ID from step 2, SKU = `ios.sku`, User Access: Full Access.
- Copy the **Apple ID** number from App Information → `tenant.json` `ios.appStoreConnectAppId`.

### Step 4 — Tenant invites us
App Store Connect → **Users and Access** → `+`:
- Our release operator, role **App Manager**, access limited to this app. With **Access to Certificates,
  Identifiers & Profiles** ticked only if we will manage signing (step 5).

### Step 5 — API key for CI
Users and Access → **Integrations** → App Store Connect API → **Team Keys** → generate:
- Name `CI uploads`, role **App Manager** (enough for TestFlight upload and build-number lookup).
- Download the `.p8` (only possible once). Note Key ID and Issuer ID.
- Save in GitHub Environment `tenant-alpha-ios`: `ASC_KEY_ID`, `ASC_ISSUER_ID`, `ASC_PRIVATE_KEY_P8_BASE64`.

### Step 6 — Signing (fastlane match, one storage per tenant)
Run once by someone with **Admin** rights on the tenant's team (the tenant, or us with a temporary Admin key).
This is the only non-read-only match run; CI and `tenant_builder` only ever run `match_readonly`:
```bash
cd application
MATCH_GIT_URL=git@github.com:our-org/signing-alpha.git \
MATCH_PASSWORD='<strong-random>' \
bundle exec fastlane match appstore \
  --app_identifier com.alphabrand.shop --team_id ABCDE12345
```
This creates the Apple Distribution certificate and App Store profile in **the tenant's** account and stores them
encrypted in a private repo used **only for this tenant**. Save `MATCH_GIT_URL`, `MATCH_PASSWORD` and a deploy key
(`MATCH_GIT_SSH_KEY`) in `tenant-alpha-ios`. CI runs match with `readonly: true`.

Never put two tenants' certificates in one match repo, and never sign a tenant's app with our team.

### Step 7 — Tenant config
Fill `ios.*` in `tenant.json` (`bundleId`, `teamId`, `appStoreConnectAppId`, `sku`, `urlScheme`), then
`tenant-builder validate --tenant alpha --platform ios --release` with the tenant's credentials available
(CI environment, or `ios/signing/alpha.env` locally, see [tenants/README.md](tenants/README.md)).

### Step 8 — Backend link file entry
Central admin → **Plans → Registered Users → Edit** (the store) → tab **Mobile App**: **Apple Team ID** `ABCDE12345`,
**iOS bundle id** `com.alphabrand.shop` (published as `ABCDE12345.com.alphabrand.shop`), **URL scheme** (if not set in
§5 step 7); **App Store URL** after release (§7.2 step 7). Check:
`curl -s https://alpha.platform.example.com/.well-known/apple-app-site-association`.

---

## 7. First release of a tenant

### 7.1 Android
1. Build and verify:
   ```bash
   tenant-builder build  --tenant alpha --platform android --artifact aab
   tenant-builder verify --tenant alpha
   ```
   Or run the `android-tenant-release` workflow with `tenant=alpha, action=build` and download the artifact.
2. **First upload is manual** (Google requires it for a new app): Play Console → Test and release → Testing →
   **Internal testing** → Create release → upload `dist/alpha/android/*.aab`. Accept **Play App Signing**.
   This locks the package name.
3. Play Console → Test and release → **App integrity** → copy the **App signing key certificate SHA-256**.
   Central admin → Plans → Registered Users → Edit (the store) → **Mobile App** → **Android SHA-256 certificate
   fingerprints**: the app signing key first, then the upload key, one per line. Check:
   `curl -s https://alpha.platform.example.com/.well-known/assetlinks.json`.
4. Add testers (email list) to internal testing, install via the opt-in link, run the smoke test (§7.3).
5. Fill everything in **Grow / Monitor → App content** and **Store listing** (§9.1). Play won't let you send
   a release to review until every item is green.
6. Personal account only: create a **Closed testing** track, upload the same build, add ≥12 testers, keep them
   opted in for 14 days, then **Apply for production** from the dashboard.
7. Tenant (or us, if granted) promotes the release to **Production** → choose countries → staged rollout
   (e.g. 20%) → **Send for review**. First review usually takes a few days.
8. After it's live, put the Play URL (`https://play.google.com/store/apps/details?id=com.alphabrand.shop`) in the
   store's **Mobile App** tab (**Google Play URL**) so the `/app` landing page links to this app.

### 7.2 iOS
1. Build, verify and upload to TestFlight (macOS or CI):
   ```bash
   tenant-builder build  --tenant alpha --platform ios --artifact ipa     # first upload: add --build-number 1
   tenant-builder verify --tenant alpha --platform ios
   tenant-builder upload --tenant alpha --platform ios --destination testflight
   ```
   Or run `ios-tenant-release` with `tenant=alpha, action=upload_testflight`.
2. Wait for processing (usually 5–30 min, email when done). Export compliance is answered automatically by
   `ITSAppUsesNonExemptEncryption=false` (Phase 0).
3. TestFlight → **Internal Testing** group → add the tenant's staff → install with the TestFlight app → smoke test (§7.3).
4. Complete the App Store listing (§9.2) for version `1.x.0`, select the build.
5. **App Review Information**: a working demo customer account for this store, contact phone/email, and notes
   explaining that this is the official app of `<store>`, sells physical goods, and payment happens through the
   store's payment gateway.
6. Tenant clicks **Add for Review → Submit**. Choose *Manually release this version* to control launch day.
7. After approval, tenant clicks **Release**. Put the App Store URL in the store's **Mobile App** tab (**App Store URL**).

### 7.3 Smoke test (both platforms, real device)
- Fresh install opens straight into this store (no store-entry screen), correct name, icon, colors, logo.
- Register, login, logout, password reset, **delete account**.
- Browse, search, cart, checkout with each payment method enabled for the store; payment return lands back in
  **this** app (install a second tenant app on the same phone to prove it).
- Orders list, order detail, invoice, returns.
- Tap `https://alpha.platform.example.com/products/<slug>` in Notes/WhatsApp → opens the app on that product.
- A link to a different store opens in the browser, not in this app.
- Photo picker (profile/returns) asks permission with the right text.
- Airplane mode → offline screen → back online → recovers.

---

## 8. Shipping updates (one tenant or all tenants)

Code is fixed once in `application/lib`; every tenant app gets it through a rebuild.

1. Merge the change to `main`, bump `version:` in `pubspec.yaml` (marketing version only; build numbers are
   allocated per tenant automatically).
2. Run the workflows with `all` (or a list of tenants):
   - `android-tenant-release` → `action=upload_internal` → every enabled tenant's AAB goes to its **internal** track.
   - `ios-tenant-release` → `action=upload_testflight` → every enabled tenant's IPA goes to its **TestFlight**.
   Read the job summary: each tenant shows ok / failed / skipped with version and build number. A failure in one
   tenant (e.g. expired certificate) does not block the others.
3. Spot-check on devices: at least the `platform` app and one tenant app per platform.
4. Promote, per tenant:
   - Android: Play Console → Internal testing release → **Promote release → Production** → staged rollout →
     Send for review. Updates usually review within hours to a day. (With *Release to production* permission,
     this can be done through `fastlane supply --track internal --track_promote_to production --rollout 0.2`.)
   - iOS: App Store Connect → new version → select build → *What's New* text → Submit for Review → Release.
5. Watch Play Console (Android vitals) and App Store Connect (Crashes / TestFlight feedback) for 48 hours, then
   raise the Android rollout to 100%.

If the backend change is not backwards compatible, raise `min_app_version` in that tenant's bootstrap **only after**
the new version is live on both stores; the app then shows the "update required" screen.

**Native vs Dart changes:** every change (Dart or native) needs a new store build. Over-the-air patching
(Shorebird) is optional future work, would need one Shorebird app per tenant, and cannot change icons, ids,
permissions, plugins or entitlements.

### Adding a new tenant later
```bash
tenant-builder create   --tenant gamma
# edit tenants/gamma/tenant.json, add icons; create GitHub Environments tenant-gamma-android / tenant-gamma-ios
tenant-builder validate --tenant gamma
tenant-builder build    --tenant gamma --platform android --artifact aab
```
Then §5, §6 and §7 for that tenant. No Flutter source is copied.

### Removing / pausing a tenant
Set `"enabled": false` in `tenants/index.json` (keeps config and history). The live store apps stay installed
for users; to remove them from sale the tenant unpublishes (Play: Publishing overview → Unpublish;
Apple: Pricing and Availability → Remove from sale). Revoke our access and rotate their keys when the contract ends.

---

## 9. Store listing checklists

Prepare per tenant in `tenants/<id>/store/` (not used by the build, just kept with the tenant):
short/long description, keywords, screenshots, feature graphic.

### 9.1 Google Play (Play Console → App content + Store listing)
- [ ] App icon 512×512 PNG, feature graphic 1024×500.
- [ ] Phone screenshots (2–8) of **this store's** products; tablet screenshots if tablets are supported.
- [ ] Short description (80 chars), full description (4000 chars), contact email, website, phone (optional).
- [ ] Privacy policy URL (tenant's own page, must mention data collected by the app).
- [ ] **App access**: demo customer login + instructions.
- [ ] Ads: "No ads" (unless the store adds them).
- [ ] Content rating questionnaire (shopping app).
- [ ] Target audience: 18+ / 13+ — not "children" (avoids Families policy).
- [ ] **Data safety**: name, email, phone, addresses, purchase history, user IDs, photos (if uploaded), app
      diagnostics; encrypted in transit; users can request deletion (link to the web deletion page).
- [ ] **Account deletion** URL: a web page where users can request deletion without the app (required).
- [ ] Government / financial / health declarations: not applicable.
- [ ] Category: Shopping. Countries and pricing: Free.

### 9.2 Apple (App Store Connect → app version + App Information + App Privacy)
- [ ] Name (30), subtitle (30), promotional text, description, keywords (100 chars), support URL, marketing URL.
- [ ] Screenshots: 6.9" iPhone (1320×2868 or 1290×2796) required; 13" iPad only if iPad is supported.
- [ ] Privacy policy URL; **App Privacy** questionnaire (same data as Play Data safety; "Data linked to you",
      not used for tracking unless analytics/ads SDKs are added).
- [ ] Age rating questionnaire, category Shopping.
- [ ] Content rights: the tenant owns the store content.
- [ ] Review info: demo account, contact, notes (see §7.2 step 5).
- [ ] Copyright: `2026 <Tenant legal name>`.
- [ ] Account deletion inside the app (exists) — mention its location in review notes.
- [ ] If a third-party social login is ever added, Sign in with Apple must be offered too (guideline 4.8).

---

## 10. Store policy risk (read before selling this)

**A successful build is not an approval.** Both stores have rules against many near-identical apps:

- **Apple 4.2.6**: apps made from a commercialized template / app-generation service are rejected **unless
  submitted directly by the provider of the app's content** (the store owner). This is why each tenant must use
  their own Apple Developer account and submit themselves. We must not submit tenant apps from our account.
- **Apple 4.3(a) spam**: many apps that are essentially the same app may be rejected and Apple may suggest one
  app. Separate icons and names alone are not enough.
- **Google Play Repetitive Content / Spam**: multiple apps with highly similar functionality and content
  without unique value are not allowed; strikes affect the developer account. Again: one app per tenant,
  published from the tenant's own account.

What helps an approval:
- Real, distinct store: own catalog, brand, domain, support contact, policies, and real stock.
- Store listing and screenshots specific to that store, not the platform.
- Reviewer demo account that works and a store with enough products to shop end-to-end.
- No "powered by platform" store directory or store picker in the white-label build (Phase 2).

Fallback if a tenant gets rejected under 4.2.6/4.3: the tenant's store remains available through the generic
`platform` app (store code / domain entry) and the web storefront. Keep that app healthy; it is the safety net.

Before onboarding many tenants, publish 1–2 pilot tenants end to end and read the review feedback.

---

## 11. Secrets reference

Stored only in GitHub Environment secrets (one environment per tenant per platform). Never in Git, `tenant.json`,
logs or build artifacts.

| Environment | Secret | Used for |
|---|---|---|
| `tenant-<id>-android` | `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS`, `ANDROID_KEY_PASSWORD` | Signing the AAB with the upload key |
| `tenant-<id>-android` | `PLAY_SERVICE_ACCOUNT_JSON_BASE64` | Upload to internal track, read version codes |
| `tenant-<id>-ios` | `ASC_KEY_ID`, `ASC_ISSUER_ID`, `ASC_PRIVATE_KEY_P8_BASE64` | TestFlight upload, build-number lookup, match auth |
| `tenant-<id>-ios` | `MATCH_GIT_URL`, `MATCH_PASSWORD`, `MATCH_GIT_SSH_KEY` | Fetching that tenant's certificate + profile (read-only) |
| repo-level | `PLATFORM_API_BASE` (not secret, a variable) | Dart define |
| repo-level (optional variables) | `IOS_RUNNER_LABEL`, `IOS_XCODE_VERSION` | Override the pinned macOS runner / Xcode |

How the tool uses them: credentials apply to a tenant only when `IOS_SIGNING_TENANT=<id>` (set by the workflow) or
`ANDROID_SIGNING_TENANT=<id>` matches, or from that tenant's own local file (`ios/signing/<id>.env`,
`android/keystores/<id>.properties`, both gitignored). They reach fastlane through the child process environment;
dry runs print variable names only. `ASC_KEY_PATH` (a `.p8` file) may replace `ASC_PRIVATE_KEY_P8_BASE64` locally.
Artifacts contain the IPA/AAB and `build-info.json` only (no keys, profiles or passwords).

Rotation: Apple distribution certificates expire after 1 year — re-run `match appstore` (Admin) before expiry;
`verify --platform ios` warns in the job log when the embedded profile expires within 30 days and fails once it has
expired. Revoke API keys / remove the service account when a tenant leaves.

---

## 12. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Play: "You uploaded an APK or Android App Bundle that was signed in debug mode" | Phase 0 signing not applied or keystore secrets missing. |
| Play: "Version code X has already been used" | Build number lookup skipped; rebuild with `--build-number` above the track's highest. |
| Play: "Package name … already exists" | Package ids are global on Google Play; pick another `applicationId`. |
| Play: cannot apply for production | Personal account: 12 testers × 14 days closed test not met. |
| Apple upload: "Missing purpose string in Info.plist" | Phase 0 `NS…UsageDescription` keys missing. |
| Apple upload: "No suitable application records were found" | App record (§6 step 3) missing or bundle id mismatch. |
| Apple upload: "bundle version must be higher" | Same build number reused; let the lookup allocate it. |
| Apple signing: "No profiles for 'com.x' were found" | match not run for this bundle id/team, or wrong `MATCH_GIT_URL`. The build expects the profile named `match AppStore <bundleId>`. |
| `tenant_builder build --platform ios` on Linux: "need macOS with Xcode" | Expected. Use `--dry-run`, or the `ios-tenant-release` workflow. |
| "store build-number lookup failed … Pass --build-number" | API key / service account missing or wrong, or the app record does not exist yet. Fix it, or give an explicit number above the store's latest. |
| "AppIdentity.xcconfig contains tenant … values" | Someone edited the shared file for a tenant. `git checkout -- ios/Flutter/AppIdentity.xcconfig`; tenant values only live in `build/workspaces/<id>/`. |
| "Select Xcode" step fails on CI | The pinned Xcode left the runner image; set repo variable `IOS_XCODE_VERSION` to one the step lists. |
| iOS verify: "unexpected entitlements" / "not allowed by the provisioning profile" | A capability was added to the project or the profile lacks it; enable only Associated Domains (+ Push when on) on the App ID and re-run match (Admin). |
| Universal/App Links open the browser | Store's **Mobile App** tab incomplete, the link host is not a registered domain of that store, wrong SHA-256 (use the **Play app signing** key), or Apple CDN cache — test on a device after a few hours. |
| Payment return opens another store's app / the platform app | Two apps share a URL scheme, or the store's **Mobile App** scheme differs from `tenant.json` `urlScheme` (the backend then ignores the app's `X-App-Scheme` and uses the platform scheme). |
| App opens the store-entry screen | Built without the tenant's `--dart-define-from-file`; always build through `tenant-builder`. |
