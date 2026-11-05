# Implementation Brief — Convert the Existing Flutter App into a White-Label Multi-Flavor Build System

> **For:** Claude Sonnet 5.5 (acting as a Senior Flutter Architect, Native Android/iOS Build Engineer, and QA Engineer)  
> **Goal:** Implement this system directly in the existing Flutter repository. Do not create a replacement app or rewrite the current application.

---

## 1. Project Context and Required Outcome

We already have a working Flutter application for a multi-vendor / multi-tenant commerce platform. The Flutter app already exists and contains real screens, business logic, API integration, authentication, state management, and possibly Firebase/native integrations.

We need to evolve the existing project into a **white-label application factory**:

- Keep **one shared Flutter/Dart codebase**.
- Produce a separate installable Android and iOS application for each tenant/store.
- Every store application must be an independent native app with its own:
  - Android `applicationId` / package ID.
  - iOS `PRODUCT_BUNDLE_IDENTIFIER` / Bundle ID.
  - App display name.
  - Launcher icon.
  - Store logo, splash/brand assets, colors, and other supported branding values.
  - API/backend tenant routing configuration.
  - Firebase app configuration, when Firebase is used.
  - Deep-link / URL scheme / associated-domain configuration, when used.
  - Store-specific native settings and credentials where applicable.
- All applications must use the same shared Flutter features and Dart source code.
- A developer must be able to fix a bug or implement a feature once in the shared codebase, then build new releases for all relevant stores without copying code between projects.
- Adding a new tenant must be a configuration/onboarding task, not a manual Flutter project duplication task.
- The mobile app must be **bound to one tenant at build time**. Do not add a tenant selector, runtime store switching, or a multi-tenant storefront switcher to the app.
- Preserve the existing backend and its tenancy model. The app should address the correct tenant using the backend's existing mechanism (tenant subdomain, tenant-specific base URL, tenant key/header, or another existing method discovered during the audit).

This is not a request to create separate Flutter repositories. It is not a request to rebuild the app from scratch.

## 2. Non-Negotiable Engineering Rules

1. **Audit before editing.** Inspect the whole repository and understand how the current app starts, loads configuration, calls APIs, authenticates users, handles push notifications, deep links, payments, and manages assets.
2. Do not assume the project uses a particular state-management library, routing library, HTTP client, Firebase setup, Gradle DSL, or Flutter version. Detect what is actually there.
3. Preserve the current app's behavior as the baseline. The existing production app should become the first/default tenant configuration, retaining its current package ID, Bundle ID, API behavior, and branding unless there is a documented reason not to.
4. Do not replace or restructure unrelated application features.
5. Do not hardcode tenant-specific values in UI widgets, screens, repositories, or API services.
6. Do not use `applicationIdSuffix` as the only identity strategy. Every tenant must be able to declare its complete, unique Android application ID explicitly. Do the equivalent for iOS Bundle IDs.
7. Android `namespace` and Android `applicationId` are different concepts. Keep the source namespace stable unless there is a compelling, documented reason to change it; configure the tenant-specific `applicationId` per flavor.
8. Never store signing private keys, passwords, App Store Connect private keys, Google Play service-account private keys, Firebase Admin SDK service-account files, or other private credentials in the tenant manifest or source repository.
9. Do not silently delete or overwrite existing signing, Firebase, URL-scheme, notification, or native configuration.
10. Generated files must be deterministic and generation must be idempotent. Running the generator twice with no input changes must not produce unrelated diffs.
11. Validate all tenant input. Sanitize flavor IDs and safely escape values before writing Gradle, XML, plist, xcconfig, or scheme files. Do not interpolate untrusted strings into build scripts.
12. Keep tenant configuration separate from secret release credentials.
13. Do not automatically upload to Google Play or App Store Connect as part of the initial implementation. Build and collect artifacts first. Store publishing must remain a separately authorized, explicitly configured operation.
14. Implement real working scripts and tests, not only documentation or pseudocode.
15. When something cannot be completed in the current environment (especially iOS builds without macOS/Xcode), explain the exact blocker and provide a CI-ready path. Do not claim an unexecuted build passed.

## 3. Start with a Repository Audit

Before changing files, inspect at least:

### Flutter / Dart
- `pubspec.yaml`, Flutter and Dart SDK constraints, dependencies, assets, fonts, localization setup, and current build commands.
- All application entry points (`main.dart`, any environment-specific entry points, bootstrap code).
- Existing app configuration/constants and how the API base URL is selected.
- State management, routing, dependency injection, networking, secure storage, local storage, and environment handling.
- Existing app name/logo/splash/icon references.
- Current tests, lints, and CI configuration.

### Android
- Whether the project uses `android/app/build.gradle` or `android/app/build.gradle.kts`.
- Android Gradle Plugin and Gradle wrapper versions.
- `namespace`, current `applicationId`, manifest placeholders, app label, icon resources, signing configuration, build types, and any existing product flavors.
- Google Services / Firebase plugin configuration and the current location of `google-services.json`.
- Intent filters, App Links, OAuth callback schemes, notification configuration, and any custom native Kotlin/Java code.

### iOS
- `ios/Runner.xcodeproj`, `ios/Runner.xcworkspace`, shared schemes, build configurations, deployment target, Bundle ID, display name, icon asset catalog, entitlements, `Info.plist`, and `Podfile`.
- Firebase initialization and `GoogleService-Info.plist` handling.
- URL schemes, Associated Domains, push notification capabilities, Apple Sign In, Google Sign In, payment callbacks, and other native integrations.
- Existing signing settings and CI setup.

### Existing Backend Contract
- Determine exactly how the backend identifies a tenant today.
- Determine whether each tenant uses a different API hostname/base URL, a tenant identifier in the request path, a header, a domain mapping, or an existing combination.
- Find all places that construct API URLs or attach tenant identifiers.
- Preserve existing authentication and token behavior.
- Do not invent a new backend tenancy protocol. If the current implementation is inconsistent, document the findings and centralize the existing behavior safely.

### Audit Deliverable
Create `docs/WHITE_LABEL_ARCHITECTURE.md` containing:
- Current app architecture and native build setup.
- Current tenant-resolution mechanism.
- Existing integrations that need per-tenant configuration.
- Risks / conflicts discovered.
- The exact files that will be changed.
- The proposed generation/build workflow.

Then implement the system. Do not stop after producing the audit unless a real blocker makes implementation unsafe.

## 4. Target Architecture

Use a **single source of truth per tenant** and a shared build tool.

A suggested structure (adapt it to the repository's conventions instead of forcing an unnecessary restructure):

```text
project/
├── lib/
│   ├── main.dart
│   ├── app/
│   ├── core/
│   ├── features/
│   ├── services/
│   └── config/
│       └── app_config.dart
│
├── tenants/
│   ├── README.md
│   ├── index.json
│   ├── _template/
│   │   ├── tenant.json
│   │   ├── assets/
│   │   │   ├── logo.png
│   │   │   ├── splash.png
│   │   │   └── app_icon.png
│   │   ├── android/
│   │   │   └── google-services.json       # when Firebase is enabled
│   │   └── ios/
│   │       └── GoogleService-Info.plist   # when Firebase is enabled
│   ├── store_alpha/
│   │   ├── tenant.json
│   │   ├── assets/
│   │   ├── android/
│   │   └── ios/
│   └── store_beta/
│       ├── tenant.json
│       ├── assets/
│       ├── android/
│       └── ios/
│
├── tool/
│   └── tenant_builder/
│       ├── ...                            # CLI, validation, generation, build orchestration
│
├── android/
├── ios/
├── test/
├── docs/
│   ├── WHITE_LABEL_ARCHITECTURE.md
│   └── WHITE_LABEL_BUILDS.md
└── .github/workflows/
    ├── android-tenant-build.yml
    └── ios-tenant-build.yml
```

The actual code layout may differ. Keep the existing `lib/` architecture intact wherever possible.

### 4.1 Tenant Registry

Use `tenants/index.json` (or another simple, machine-readable registry) to list active tenant IDs. Each tenant has its own `tenant.json`. The registry should allow enabling/disabling a tenant for builds without deleting its configuration.

Example registry:

```json
{
  "schemaVersion": 1,
  "tenants": [
    { "id": "store_alpha", "enabled": true },
    { "id": "store_beta", "enabled": true }
  ]
}
```

### 4.2 Example Tenant Manifest

Implement a documented schema and JSON validation. Adjust field names to fit the project, but support at least the following concepts:

```json
{
  "schemaVersion": 1,
  "id": "store_alpha",
  "displayName": "Store Alpha",
  "android": {
    "applicationId": "com.example.storealpha"
  },
  "ios": {
    "bundleId": "com.example.storealpha"
  },
  "backend": {
    "baseUrl": "https://api.storealpha.example.com",
    "tenantIdentifier": "store_alpha",
    "tenantResolution": "existing_project_strategy"
  },
  "branding": {
    "primaryColor": "#2457A7",
    "secondaryColor": "#F3F5F8",
    "logo": "assets/logo.png",
    "splash": "assets/splash.png",
    "appIcon": "assets/app_icon.png"
  },
  "firebase": {
    "enabled": true,
    "androidConfig": "android/google-services.json",
    "iosConfig": "ios/GoogleService-Info.plist"
  },
  "deepLinks": {
    "urlScheme": "storealpha",
    "associatedDomains": [],
    "androidHosts": []
  },
  "features": {
    "featureFlagExample": true
  }
}
```

Notes:
- The values above are examples only. Do not copy example package IDs, API domains, Firebase settings, or values into production.
- `tenantResolution` must map to the actual backend behavior discovered in the audit. Do not invent a header or subdomain convention.
- If the current application has no per-store API hostname, use its existing API host and provide the fixed tenant identifier through the existing backend contract.
- Avoid adding optional manifest fields that the project does not need. Document every supported field.
- App Store / Play Store listing metadata and signing credentials should not be mixed into the runtime app manifest.

### 4.3 Configuration Responsibilities

Create a single typed, immutable app configuration abstraction, for example `AppConfig`, that the existing app can use for:
- App/brand display values shown inside the UI.
- Logo and splash asset paths.
- Theme colors and other branding tokens.
- Fixed tenant identity and backend routing values.
- Feature flags that are intentionally build-time tenant-specific.

The UI and services must consume this configuration through dependency injection or the project's existing configuration pattern. Do not introduce a second competing configuration system.

**Important:** Native identity is not controlled only by Dart. Android `applicationId`, iOS Bundle ID, launcher icons, entitlements, and Firebase plist/json selection must be correctly configured at native build time too.

Do not create runtime tenant switching. The tenant ID should be immutable for a particular binary. If the app is using a shared API domain and a tenant header, attach the fixed tenant value centrally in the existing API client/interceptor, not independently in every feature. If it uses per-tenant API domains, configure the correct base URL centrally.

## 5. Android Flavor Requirements

Implement tenant-specific Android product flavors using the Gradle DSL already present in the repository (Groovy or Kotlin DSL). Follow the current official Flutter flavor conventions and the project's actual Gradle/AGP version.

For each enabled tenant:

- Create a product flavor whose safe flavor name is derived from the tenant ID.
- Set its complete explicit `applicationId` from the tenant manifest.
- Keep the app's `namespace` stable unless the audit establishes a real need to change it.
- Set a flavor-specific app label, for example using `resValue("string", "app_name", ...)` and `android:label="@string/app_name"`.
- Provide flavor-specific launcher icon resources. The final generated APK/AAB must use the right tenant icon, including adaptive icon resources if the project currently uses them.
- Select the correct Firebase `google-services.json` for that tenant if Firebase is enabled. Use the official Google Services Gradle plugin's supported flavor/source-set layout or a deterministic pre-build copy step. Do not let one tenant's Firebase config leak into another tenant's build.
- Preserve the current app permissions, manifest entries, notification setup, queries, providers, and native integrations.
- Support debug/profile/release build variants as appropriate.
- Preserve and document the existing signing behavior. Do not sign release builds with debug keys.

Do not implement only a suffix-based package name. Example identities must be explicit and unique:

```text
com.brand.alpha
com.brand.beta
```

### Android validation
- Verify each flavor builds independently.
- Verify the resulting package ID and app label from the built artifact (using available Android SDK tools such as `apkanalyzer` / `aapt` where available).
- Verify icons and Firebase app IDs correspond to the selected tenant.
- Verify two tenant apps can be installed simultaneously on the same device and do not overwrite each other.

## 6. iOS Flavor / Scheme Requirements

Xcode does not use the Android term `product flavor`; implement the equivalent using **shared Xcode schemes and per-tenant build configurations**.

For every enabled tenant, provide:
- A shared Xcode scheme that Flutter CLI can select with `--flavor <tenant_id>`.
- `Debug-<tenant_id>`, `Profile-<tenant_id>`, and `Release-<tenant_id>` configurations (or the exact equivalent supported by the current project).
- Tenant-specific `PRODUCT_BUNDLE_IDENTIFIER`.
- Tenant-specific `APP_DISPLAY_NAME`, wired into `CFBundleDisplayName` in `Info.plist`.
- Tenant-specific app icon asset catalog selection (for example `ASSETCATALOG_COMPILER_APPICON_NAME`).
- Tenant-specific Firebase plist selection/copy behavior when Firebase is enabled.
- Correct per-tenant URL schemes, associated domains, entitlements, and callback registrations where the project uses them.

Additional requirements:
- Schemes must be shared so Flutter tooling and CI can see them.
- Update the existing Podfile/configuration mapping carefully for all new configurations. Do not blindly duplicate or break CocoaPods configuration.
- Avoid editing `project.pbxproj` with fragile regex replacements. Use a safe, deterministic generator or supported project tooling. Preserve unrelated Xcode settings and target membership.
- Confirm the right configuration is used for Runner and the relevant project/target build configurations.
- Preserve the existing iOS deployment target and native capabilities unless a change is required and documented.

### Scalability requirement

The tenant manifest must remain the source of truth. A developer must not have to manually open Xcode and create a scheme/configuration every time a new tenant is added. Implement an idempotent generator/registration step that creates or updates the required native Xcode configuration from the tenant manifest. The generator may use a safe Xcode project library/tool already available in the environment; document any tool prerequisite.

If the existing project or tooling makes fully dynamic scheme generation unsafe, implement a clearly documented, deterministic generated configuration approach and explain its limits. Do not pretend the iOS scheme is generated if it is actually a manual step.

### iOS validation
- List schemes with `xcodebuild -list` on macOS and verify each enabled tenant scheme exists.
- Inspect build settings and verify the selected Bundle ID, app display name, and icon catalog.
- Verify Firebase plist and entitlements correspond to the selected tenant.
- Verify two tenant apps have distinct Bundle IDs and can coexist on one iPhone.

## 7. App Assets and Branding

Each tenant should provide a small, documented set of source assets. At minimum:
- High-resolution square app icon source (transparent background policy documented; include adaptive Android foreground/background if required).
- Store logo.
- Splash/launch artwork only if the existing project uses it.
- Optional brand colors and supported visual tokens.

Implement a predictable asset generation or validation process:
- Check that required files exist and are valid images.
- Check minimum image dimensions and supported formats.
- Generate/copy platform-specific icon variants as required by Android and iOS.
- Keep branding assets isolated per tenant.
- Ensure unused tenant assets are not accidentally bundled into every application when the current Flutter SDK supports flavor-specific assets. If not, use a safe, documented fallback that avoids leaking other stores' assets or unnecessarily inflating every binary.
- Keep the existing design and component architecture. Replace only hardcoded brand values with the centralized configuration where necessary.

Do not modify product images or store content. Those should continue to come from the existing API unless the current architecture explicitly says otherwise.

## 8. Firebase, Notifications, and Other Native Integrations

Audit every integration that is registered to an Android package ID or iOS Bundle ID. This may include:
- Firebase Core / FCM.
- Google Sign-In.
- Apple Sign-In.
- Facebook SDK.
- Maps SDK.
- Payment SDK callback URLs.
- Deep links / App Links / Universal Links.
- Analytics/crash reporting.
- Any custom native plugin or callback scheme.

For Firebase specifically:
- Each Android application ID and each iOS Bundle ID must have the correct Firebase app registration/configuration.
- A single Firebase project may be used for multiple registered apps when that matches the product's isolation requirements; separate Firebase projects per tenant are also possible if required. Do not assume one Firebase project per tenant is mandatory.
- Support a tenant-specific `google-services.json` and `GoogleService-Info.plist` selection.
- Verify FCM tokens are registered against the correct backend tenant and that push notifications reach the correct store app.
- Never add Firebase Admin SDK credentials to the mobile app.

For all native integrations, document which settings are tenant-specific, which may safely be shared, and what must be registered in the provider's console for a new Bundle ID/package ID.

## 9. Shared Dart Runtime Configuration

Implement a clean way to resolve the current build's tenant configuration.

Preferred behavior:
- Resolve the active tenant from the selected flavor / generated compile-time configuration.
- Fail fast with a clear error when a production build has no valid tenant configuration.
- Do not silently fall back to another store or to a default tenant in a production flavor.
- Keep the app's current bootstrap order intact (Firebase, storage, API clients, localization, dependency injection, etc.) while injecting the selected config at the appropriate point.
- Avoid reading a mutable tenant ID from local storage or remote configuration to decide which store this binary represents.
- Support a debug-only configuration override only if it is genuinely useful and cannot accidentally alter a release binary's tenant binding.

Use `--dart-define-from-file` / `String.fromEnvironment`, Flutter's supported `appFlavor`, or a generated typed Dart config as appropriate for the current Flutter version. Do not use Dart defines as a substitute for native Bundle ID/package ID configuration.

## 10. Tenant Build Generator / CLI

Build a small, maintainable CLI under `tool/tenant_builder/` (or the repository's established tooling folder). Prefer Dart if that integrates cleanly with the existing Flutter repo; a small Python/Ruby script is acceptable if it makes native generation materially safer. Avoid adding a large dependency without a reason.

Required commands (the exact syntax can be adapted, but equivalent functionality must exist):

```bash
# List registered tenants
<tenant-cli> list

# Create a new tenant folder and starter manifest/assets from _template
# This command must refuse to overwrite an existing tenant.
<tenant-cli> create --tenant store_gamma

# Validate a tenant manifest, branding assets, native config references, and IDs
<tenant-cli> validate --tenant store_alpha

# Generate/update native flavor/scheme/configuration files from the registry
<tenant-cli> generate --tenant store_alpha
<tenant-cli> generate --all

# Run the app for a tenant on Android/iOS where supported
<tenant-cli> run --tenant store_alpha --platform android

# Build one tenant's Android App Bundle
<tenant-cli> build --tenant store_alpha --platform android --artifact aab --mode release

# Build one tenant's Android APK for internal testing
<tenant-cli> build --tenant store_alpha --platform android --artifact apk --mode release

# Build one tenant's iOS IPA (must run on macOS with Xcode)
<tenant-cli> build --tenant store_alpha --platform ios --artifact ipa --mode release

# Build all enabled tenants for a platform (sequentially unless isolated workspaces are implemented)
<tenant-cli> build --all --platform android --artifact aab --mode release
```

The CLI must:
- Validate that tenant IDs are registered and enabled.
- Validate Android application IDs and iOS Bundle IDs are syntactically valid and globally unique within the registry.
- Validate flavor names are safe and unique.
- Validate required tenant fields, URLs, image assets, Firebase config paths, and native integration settings.
- Give actionable errors with the exact missing file/field.
- Have a dry-run or plan mode where practical.
- Allow a single-tenant build without building every tenant.
- Build tenants sequentially when they share the same Flutter build output directory, unless isolated workspaces/output directories are used. Avoid parallel builds that overwrite shared generated files or artifacts.
- Copy final artifacts to a stable tenant-specific output structure, for example:

```text
dist/
├── store_alpha/
│   ├── android/
│   │   ├── store_alpha-release.aab
│   │   └── store_alpha-release.apk
│   └── ios/
│       └── store_alpha-release.ipa
└── store_beta/
    └── ...
```

- Write a small build metadata file alongside each artifact, containing tenant ID, app IDs, version/build number, Git commit SHA, build timestamp, and platform. Do not include secrets.
- Never place one tenant's Firebase config, branding, or package identity into another tenant's artifact.
- Do not overwrite a release artifact from another tenant.
- Return a non-zero exit code on validation or build failure.

## 11. Expected Build Commands

Provide and document direct Flutter CLI equivalents, adjusted to the real flavor names and config mechanism.

Typical examples:

```bash
# Android debug/run
flutter run --flavor store_alpha -t lib/main.dart \
  --dart-define-from-file=tenants/store_alpha/dart_defines.json

# Android release App Bundle
flutter build appbundle --release --flavor store_alpha -t lib/main.dart \
  --dart-define-from-file=tenants/store_alpha/dart_defines.json

# Android release APK
flutter build apk --release --flavor store_alpha -t lib/main.dart \
  --dart-define-from-file=tenants/store_alpha/dart_defines.json

# iOS IPA (requires macOS + Xcode)
flutter build ipa --release --flavor store_alpha -t lib/main.dart \
  --dart-define-from-file=tenants/store_alpha/dart_defines.json
```

Do not copy these commands blindly if the repository requires a different target, scheme name, environment file, signing method, or Flutter version. Make the actual generated CLI the preferred workflow and verify the commands work.

## 12. CI/CD Requirements

Add CI workflows only after the local generator/build flow works.

### Android workflow
- Use a Linux runner if it satisfies the project's Android toolchain.
- Install the pinned Flutter version and Java/Android dependencies required by the repository.
- Accept a selected tenant or an `all` option through a manual workflow dispatch, and optionally run on selected tags/branches.
- Run formatting, analysis, tests, tenant validation, generation, and release build.
- Configure Android signing through GitHub Actions Secrets/secure files, never committed files.
- Upload artifacts under tenant-specific names and paths.

### iOS workflow
- Use a macOS runner with a compatible Xcode version.
- Install the pinned Flutter version, CocoaPods dependencies, and required Ruby tooling.
- Accept tenant selection or `all` through manual workflow dispatch.
- Configure signing and provisioning using secure secrets / an established Fastlane Match workflow if appropriate.
- Support customer-owned Apple Developer accounts where required. Do not assume all customer apps can be submitted from one provider account.
- Build and upload IPA artifacts to CI storage. Do not submit to App Store Connect automatically in this implementation.

### Release security
- Separate build-time public configuration from signing/upload secrets.
- Use GitHub Environments with approvals for production builds if appropriate.
- Avoid printing secrets in logs.
- Do not store certificates, provisioning profiles, keystores, or API private keys in the tenant manifest.

## 13. Optional Shorebird Support — Do Not Make It a Prerequisite

Keep standard Play Store / App Store release builds fully functional without Shorebird.

After the normal tenant flavor build flow is tested, evaluate optional Shorebird Code Push integration. If added:
- Each tenant flavor must map to its own Shorebird app identity/release stream.
- Every release and patch command must specify the correct `--flavor` and the exact same target and Dart defines used by the corresponding store release.
- A shared Dart code fix must be patched separately to every tenant app/release that should receive it.
- Document that Dart-only changes may be eligible for a patch, but native code, plugin, permission, entitlement, app icon, Bundle ID/package ID, and other native changes require a regular store release.
- Do not run `shorebird init --force` on an app that already has published Shorebird releases.
- Keep a staging/preview process before production patches.
- Do not claim Shorebird removes the need to publish native app updates or guarantees app-store policy acceptance.

If Shorebird is not added in this task, document a clean future integration point without compromising the flavor implementation.

## 14. App Store / Play Store Distribution Risk — Must Be Documented

This architecture is technically capable of generating many distinct binaries, but **technical build success does not guarantee store approval**.

Current platform policies include restrictions on repetitive or substantially similar apps:
- Apple App Review Guidelines include rules concerning minimum functionality, commercialized template/app-generation services, and spam/repeated apps (notably sections 4.2.6 and 4.3(a)). Apple states that apps created from a commercialized template/app-generation service should be submitted directly by the provider of the app's content, and warns against creating multiple Bundle IDs for essentially the same app.
- Google Play's Repetitive Content policy disallows multiple apps with highly similar functionality, content, and user experience without unique value.

Therefore:
- Do not promise that every generated tenant app will be accepted.
- Do not implement automatic mass publication.
- Document that each tenant should own/authorize its app listing and, where required, submit through the account of the content provider/customer.
- Each store should have genuine brand ownership, store-specific content/services, accurate listing metadata, privacy disclosures, support details, and a meaningful user experience.
- Before commercializing mass generation, review the current Apple and Google policies and obtain platform guidance where necessary.

Official references:
- Flutter Android flavors: https://docs.flutter.dev/deployment/flavors
- Flutter iOS/macOS flavors: https://docs.flutter.dev/deployment/flavors-ios
- Apple App Review Guidelines: https://developer.apple.com/app-store/review/guidelines/
- Google Play Developer Program Policy: https://support.google.com/googleplay/android-developer/answer/18265346
- Shorebird flavor guide (optional): https://docs.shorebird.dev/code-push/guides/flavors/

## 15. Testing and Acceptance Criteria

Do not mark this task complete until the applicable checks below are implemented and run.

### Configuration / Generator tests
- [ ] A valid tenant can be loaded and validated.
- [ ] Unknown, disabled, malformed, or duplicate tenant IDs fail with clear errors.
- [ ] Duplicate Android application IDs are rejected.
- [ ] Duplicate iOS Bundle IDs are rejected.
- [ ] Duplicate/invalid flavor names are rejected.
- [ ] Missing logos/icons/Firebase files fail with a useful message when required.
- [ ] Invalid URLs, colors, or manifest schema versions are rejected.
- [ ] Running generation twice is idempotent.
- [ ] Existing unmanaged native settings are preserved.
- [ ] No secret material is written to generated public config or logs.

### Dart / application tests
- [ ] The active flavor resolves to the correct immutable tenant configuration.
- [ ] Production startup fails clearly if tenant config is missing or invalid.
- [ ] API requests use the intended tenant backend resolution mechanism.
- [ ] There is no tenant picker or runtime switching in the app.
- [ ] Branding values are centralized and the existing business flows continue to work.
- [ ] Existing tests continue to pass; add focused tests for the new configuration layer.

### Android checks
- [ ] Build a debug APK for at least two different tenants.
- [ ] Build a release AAB for at least two different tenants if signing is available.
- [ ] Inspect and verify distinct package IDs and app labels.
- [ ] Verify each app has the right launcher icon and Firebase config.
- [ ] Install both apps on one Android device/emulator; confirm they coexist.
- [ ] Test login, API calls, push notification registration (if configured), and deep links (if used).

### iOS checks (on macOS/Xcode)
- [ ] Confirm the expected tenant schemes appear in `xcodebuild -list`.
- [ ] Build simulator/device configuration as practical.
- [ ] Build IPA when signing credentials are available.
- [ ] Inspect and verify unique Bundle IDs, display names, and app icon asset names.
- [ ] Verify correct Firebase plist, URL schemes, and entitlements.
- [ ] Confirm two tenant apps can coexist on one iPhone.

### Regression
- [ ] The original/default tenant still behaves like the existing app.
- [ ] Existing authentication, checkout, orders, notifications, deep links, and any payment flows are not broken.
- [ ] No tenant's branding or Firebase config appears in another tenant's binary.
- [ ] Shared application code remains in one codebase.

If a platform build cannot be run because the current machine lacks its native toolchain, run all available checks, state what was not run, and ensure the CI workflow can run it.

## 16. Documentation to Deliver

Create/update:

1. `docs/WHITE_LABEL_ARCHITECTURE.md` — architecture, tenant resolution, configuration ownership, native identity model, and limitations.
2. `docs/WHITE_LABEL_BUILDS.md` — exact commands for adding a tenant, validating, generating native files, running locally, building Android APK/AAB, building iOS IPA, and building all enabled tenants.
3. `tenants/README.md` — tenant manifest schema, required assets, Firebase registration steps, deep-link setup, and secure credential rules.
4. A sample tenant configuration based on the current app's existing configuration (retain current IDs as the default tenant; clearly mark any values that need to be supplied by the owner).

The documentation must provide an exact end-to-end example such as:

```bash
<tenant-cli> create --tenant store_gamma
# Edit tenants/store_gamma/tenant.json and add the store's real brand assets/Firebase files.
<tenant-cli> validate --tenant store_gamma
<tenant-cli> generate --tenant store_gamma
<tenant-cli> build --tenant store_gamma --platform android --artifact aab --mode release
# Run the equivalent iOS IPA build on macOS with Xcode and valid signing setup.
```

The documentation must distinguish between:
- Adding a tenant configuration.
- Registering the Android app in Firebase / other provider consoles.
- Registering the iOS app in Firebase / Apple Developer / other provider consoles.
- Generating/building the binary.
- Signing and publishing the binary.

## 17. Implementation Workflow

Work in this order:

1. Audit repository and record baseline behavior/build status.
2. Document current architecture and existing tenant resolution.
3. Define the tenant manifest schema and migrate the current app into the first/default tenant without changing its identity.
4. Implement typed shared Dart configuration and remove only the hardcoded tenant-specific values that need to become configurable.
5. Implement Android product flavors and validate at least two tenant identities.
6. Implement iOS shared schemes/configurations and safe generator support.
7. Implement tenant asset/icon selection and Firebase/native config selection.
8. Implement CLI commands for list, validate, generate, run, and build.
9. Add unit/configuration tests and platform smoke tests.
10. Add CI workflows for Android and iOS with safe secrets handling.
11. Run applicable tests/builds and fix regressions.
12. Update all documentation and provide a concise final implementation report.

Do not change ten unrelated things in the same pass. Keep changes scoped, reviewable, and compatible with the current project.

## 18. Final Report Format

At the end, report:

- What changed and the architecture now in place.
- The final tenant folder/manifest format.
- Exact commands to add and build a tenant.
- Which Android builds were actually run and their result.
- Which iOS builds were actually run and their result; clearly state if macOS/Xcode/signing was unavailable.
- Test and analysis results.
- Native/provider setup still required from the app owner (Firebase app registrations, signing credentials, Apple/Google accounts, domains, etc.).
- Any limitations or store-policy risks.
- Files changed and important generated files.

**Definition of done:** one existing Flutter codebase can generate independently installable, correctly branded, tenant-bound Android and iOS app builds from tenant configuration, with repeatable scripts and no manual copying of Flutter source code.
