# White-label builds: commands

Architecture: [WHITE_LABEL_ARCHITECTURE.md](WHITE_LABEL_ARCHITECTURE.md). Tenant schema and secrets:
[application/tenants/README.md](../../application/tenants/README.md). Store accounts and release process:
[application/PUBLISH_TO_STORES.md](../../application/PUBLISH_TO_STORES.md).

Everything runs from `application/` with the Flutter version pinned in CI (**3.47.1**; `pubspec.yaml`
needs Dart ≥ 3.13.1). Alias the tool:

```bash
cd application
alias tenant-builder='dart run tool/tenant_builder/bin/tenant_builder.dart'
tenant-builder --help
```

The tool runs `flutter` from `PATH`, or `FLUTTER_BIN` if set, and fastlane as `bundle exec fastlane` from
`application/` (or `FASTLANE_BIN`). Exit codes: `0` ok, `1` validation/build/verify/upload failure, `64` usage error.

One-time fastlane setup (Ruby ≥ 3.1; macOS for iOS, any OS for Android):
```bash
cd application
bundle install          # Gemfile pins fastlane ~> 2.228
git add Gemfile.lock    # commit the lock file produced on the first install (not committed yet: no Ruby where this was written)
```

## Commands

| Command | What it does |
|---|---|
| `list [--json] [--enabled] [--platform android]` | Registered tenants; `--json` is what CI uses for its matrix. |
| `create --tenant <id> [--display-name "…"]` | Copies `tenants/_template`, registers the tenant **disabled**. Refuses existing ids. |
| `validate --tenant <id>\|--all [--platform android\|ios] [--release] [--api-base URL]` | Schema, ids, uniqueness, URLs, colors, icon size/alpha, secrets-not-in-repo. `--release` also requires real (non-placeholder) values, the tenant's upload key and `PLATFORM_API_BASE`. |
| `generate --tenant <id>\|--all [--platform …] [--check]` | Writes `build/tenant_config/<id>.json` (Dart defines) and `android/app/src/<id>/` (icons, extra link hosts). Idempotent; `--check` exits 1 if anything would change; `--all` removes generated folders of disabled tenants. |
| `run --tenant <id> --platform android [-- <flutter run args>]` | `flutter run --flavor <id>` with the tenant's defines. |
| `build --tenant <id>\|--all --platform android [--artifact aab\|apk] [--mode release\|profile\|debug] [--build-number N] [--build-name X.Y.Z] [--api-base URL] [--dry-run] [--skip-verify]` | validate → generate → `flutter build` → copy to `dist/<id>/android/` + `build-info.json` → `verify`. `--all` builds enabled tenants **sequentially** and prints an ok/FAILED summary; one failure does not stop the others. |
| `verify --tenant <id> [--artifact-path F]` | Reads the artifact with `aapt2`/`apksigner`/`keytool`: package id, label, version vs `build-info.json`, App Links hosts and scheme, launcher icon pixels = this tenant's, **no other tenant's package id / hosts / icon inside**, Dart snapshot bound to the store host, release not debug-signed. |
| `upload --tenant <id>\|--all --platform android [--track internal\|alpha\|beta] [--release-status draft\|completed] [--dry-run]` | Lane `android upload_testing` with the last release AAB to a **testing** track. `production` is refused (tool and lane). Needs `PLAY_SERVICE_ACCOUNT_JSON_PATH`. |
| `generate … --platform ios` | Writes `build/workspaces/<id>/overlay/` (AppIdentity/TenantSigning/Release xcconfig, entitlements, AppIcon set, ExportOptions.plist). Never touches the shared `ios/`; refuses if the shared `AppIdentity.xcconfig` holds a store tenant's values. |
| `run --tenant <id> --platform ios [-- <flutter run args>]` | macOS only. Syncs the tenant workspace and runs `flutter run` there (simulator or device). |
| `build --tenant <id>\|--all --platform ios [--artifact ipa] [--build-number N] [--build-name X.Y.Z] [--api-base URL] [--dry-run] [--skip-verify]` | macOS only (`--dry-run` works anywhere), release only. validate --release → build-number lookup → generate → sync `build/workspaces/<id>/app` → lane `ios match_readonly` → `flutter build ipa --release --export-options-plist=ExportOptions.plist --dart-define-from-file=…` (**no `--flavor`**) in the workspace → `dist/<id>/ios/<id>-<version>+<build>.ipa` + `build-info.json` → verify. |
| `verify --tenant <id> --platform ios [--artifact-path F]` | Pure-Dart IPA inspection (runs on Linux): Info.plist (binary) bundle id, display name, version/build vs `build-info.json`, URL schemes = this tenant's only, purpose strings, compiled icon pixels = this tenant's (also Xcode "CgBI" PNGs); signed entitlements from the code signature (team, application-identifier, associated domains = this tenant's, no `get-task-allow`, no unused capabilities, aps-environment only with push); embedded profile (App Store, team/app id, not expired, allows every signed entitlement); Firebase plist only with push and for this bundle id; **no other tenant's bundle/package id or hosts anywhere**; Dart snapshot bound to the store host. On macOS also `codesign --verify --deep --strict`. |
| `upload --tenant <id>\|--all --platform ios [--destination testflight] [--artifact-path F] [--dry-run]` | Re-verifies the IPA, then lane `ios upload_testflight` (fastlane pilot, `skip_submission`, no external distribution) to **the tenant's own** App Store Connect app (`ios.appStoreConnectAppId`). There is no submit/release command. |

## Typical flows

### Add a tenant
```bash
tenant-builder create --tenant gamma --display-name "Gamma Shop"
# edit tenants/gamma/tenant.json (store.host, applicationId, bundleId, urlScheme, teamId)
# replace tenants/gamma/assets/app_icon.png (1024×1024, opaque)
# set "enabled": true for gamma in tenants/index.json
tenant-builder validate --tenant gamma
tenant-builder generate --tenant gamma
tenant-builder build --tenant gamma --platform android --artifact apk --mode debug   # smoke test
```
Then provider registration (separate steps, PUBLISH_TO_STORES.md §5/§6): Play app record + upload key +
service account + GitHub Environment `tenant-gamma-android`; the store's **Mobile App** tab in the central admin (Plans → Registered Users → Edit; ids, SHA-256, scheme);
Apple/Firebase as applicable.

### Local Android release build
```bash
# once: android/keystores/gamma.properties (gitignored)
#   storeFile=gamma-upload.jks
#   storePassword=…
#   keyAlias=upload
#   keyPassword=…
export PLATFORM_API_BASE=https://api.yourplatform.com
tenant-builder build --tenant gamma --platform android --artifact aab --mode release --build-number 12
ls dist/gamma/android/   # gamma-1.0.0+12.aab  build-info.json
```

### Local iOS release build (Mac with Xcode 26)
```bash
# once per tenant: ios/signing/gamma.env (gitignored), see tenants/README.md
#   MATCH_GIT_URL=git@github.com:our-org/signing-gamma.git
#   MATCH_PASSWORD=…
#   ASC_KEY_ID=…  ASC_ISSUER_ID=…  ASC_KEY_PATH=AuthKey_XXXX.p8
export PLATFORM_API_BASE=https://api.yourplatform.com
tenant-builder build  --tenant gamma --platform ios          # build number = TestFlight latest + 1
tenant-builder upload --tenant gamma --platform ios --destination testflight
ls dist/gamma/ios/   # gamma-1.0.0+13.ipa  build-info.json
```
On Linux: `tenant-builder build --tenant gamma --platform ios --dry-run` prints the exact lanes and flutter command;
`tenant-builder verify --tenant gamma --platform ios --artifact-path some.ipa` checks an IPA built elsewhere.

### All tenants
```bash
tenant-builder build --all --platform android --artifact aab --mode release
tenant-builder build --all --platform ios                     # sequential, per-tenant credentials and workspace
tenant-builder upload --all --platform ios --destination testflight
```
Each tenant uses only its own credentials (`*_SIGNING_TENANT=<id>` in the environment, or its own local file);
with `--all` locally that means per-tenant files. One failure does not stop the others; the summary lists each
tenant's result, version and artifact.

### CI
GitHub → Actions → **android-tenant-release** → Run workflow: `tenant` = `gamma`, `gamma,alpha` or `all`;
`action` = `build` (artifact only) or `upload_internal` (also Play internal track); optional `build_number`.
Each tenant runs in its own job with GitHub Environment `tenant-<id>-android` (secrets per
PUBLISH_TO_STORES.md §11) and concurrency group `release-android-<id>`. Artifacts are uploaded as
`android-<id>-<run>`.

GitHub → Actions → **ios-tenant-release** → Run workflow: `tenant` (id, list or `all`), `action` = `build` or
`upload_testflight`, optional `build_number` / `marketing_version`. One macOS job per tenant with environment
`tenant-<id>-ios`, concurrency `release-ios-<id>`, temporary keychain, read-only match, cleanup in `if: always()`,
artifact `ios-<id>-<run>`, and a job summary row (bundle id, version (build), artifact, commit, upload status).

## Direct Flutter equivalents

The tool is the supported path (it also generates icons and verifies). What it runs:

```bash
dart run tool/tenant_builder/bin/tenant_builder.dart generate --tenant demo
flutter run --flavor demo -t lib/main.dart --dart-define-from-file=build/tenant_config/demo.json
flutter build appbundle --release --flavor demo -t lib/main.dart \
  --dart-define-from-file=build/tenant_config/demo.json --build-name 1.0.0 --build-number 1
flutter build apk --debug --flavor platform -t lib/main.dart \
  --dart-define-from-file=build/tenant_config/platform.json
```

- With flavors declared, a plain `flutter build apk` (no `--flavor`) does not produce an APK; always pass a flavor.
- A store flavor built without its define file fails in Gradle (`Flavor 'demo' must be built with
  --dart-define-from-file=…`); a mismatched define file makes the app show a configuration error at startup.
- `flutter run --flavor platform` without defines still works and is the generic app (as before).
- Add `--dart-define=PLATFORM_API_BASE=https://…` (or put it in the env before `generate`) for a real backend.

## Versions

Marketing version from `pubspec.yaml` (`version: 1.4.0+7`) unless `--build-name`. Build number, per tenant,
for **release** builds: the store's latest + 1 (lane `android latest_version_code` over all Play tracks /
`ios latest_build_number` from TestFlight). `--build-number N` overrides it but must be higher than the store's
latest; if the lookup fails, the build stops unless `--build-number` is given (no guessing). Debug/profile builds
and `--dry-run` use `pubspec.yaml`. The first Android upload of a new app (manual, §7.1) needs `--build-number`.

## Generated / ignored files

| Path | Source | Committed |
|---|---|---|
| `build/tenant_config/<id>.json` | `generate` | no (`/build/`) |
| `android/app/src/<id>/` | `generate` (marker `.tenant_builder.json`) | no |
| `android/keystores/` | you | no |
| `dist/<id>/<platform>/` | `build` | no |
| `build/workspaces/<id>/overlay/` | `generate --platform ios` (marker `../.tenant_builder.json`) | no |
| `build/workspaces/<id>/app/` | `build`/`run --platform ios` (snapshot + overlay) | no |
| `ios/signing/<id>.env`, `*.p8`, `*.p12`, `*.mobileprovision` | you | no (gitignored) |
| `Gemfile`, `Gemfile.lock`, `fastlane/Fastfile` | repo | yes |

`generate` never touches `android/app/src/{main,debug,profile}` and refuses to overwrite an
`android/app/src/<id>/` folder it did not create. `generate --all` removes generated `android/app/src/<id>/` and
`build/workspaces/<id>/` folders of tenants that are no longer enabled.

The shared `ios/` tree is the `platform` app and is never modified by the tool: `flutter run` without the tool
still builds the generic app. Tenant iOS identity exists only inside `build/workspaces/<id>/`.

## Device checks (manual, per tenant)

```bash
adb install -r dist/demo/android/demo-1.0.0+1-debug.apk
adb install -r dist/platform/android/platform-1.0.0+1-debug.apk   # both coexist (different package ids)
adb shell pm get-app-links com.demobrand.example.shop
adb shell am start -a android.intent.action.VIEW -d https://demo.platform.example.com/products/x
```
iOS: install two tenants' TestFlight builds on one iPhone; both appear with their own name/icon. Universal
Links: long-press a `https://<store.host>/products/x` link in Notes → "Open in <app>"; `xcrun devicectl` /
Console logs `swcd` for association problems. Then the smoke test in PUBLISH_TO_STORES.md §7.3.

## Not verifiable without a Mac / credentials

`flutter build ipa`, `match`, `codesign`, the TestFlight upload and the `ios-tenant-release` workflow have not been
run yet (written on Linux, no Apple account). Before the first tenant: run the workflow with `action=build` for a
pilot tenant, download the artifact, and `verify` it; then `upload_testflight`.
