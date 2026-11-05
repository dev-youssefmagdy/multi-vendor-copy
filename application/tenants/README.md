# Tenants (one native app per store)

Each folder here is one installable app built from the shared Flutter code. The folder is **public
configuration only** — never put keys, passwords, keystores, `.p8`/`.p12` files or service-account JSON
here (the validator rejects them). Commands: [docs/mobile/WHITE_LABEL_BUILDS.md](../../docs/mobile/WHITE_LABEL_BUILDS.md).
Store setup: [PUBLISH_TO_STORES.md](../PUBLISH_TO_STORES.md).

```text
tenants/
├── index.json            registry: platformDomain + which tenants are enabled
├── _template/            copied by `tenant_builder create`
├── platform/             the generic store-browser app (today's app, runtime store picker)
└── demo/                 sample white-label store (PLACEHOLDER ids; not for release)
    ├── tenant.json
    └── assets/
        ├── app_icon.png             1024×1024 PNG, opaque (iOS rejects alpha)
        └── app_icon_foreground.png  optional Android adaptive-icon foreground (may be transparent)
```

## index.json

```json
{
  "schemaVersion": 1,
  "platformDomain": "platform.example.com",
  "tenants": [
    { "id": "platform", "enabled": true },
    { "id": "demo", "enabled": true }
  ]
}
```

| Field | Rule |
|---|---|
| `platformDomain` | Domain whose subdomains are the stores. The `platform` app verifies App Links for `*.<platformDomain>`. |
| `tenants[].id` | `^[a-z][a-z0-9]{2,30}$`, unique. Also the Android flavor name and folder name. Reserved: `main debug profile release test* androidtest template all none default lint`. |
| `tenants[].enabled` | `false` keeps the config but removes the Android flavor and skips the tenant in `--all`. |

## tenant.json

```json
{
  "schemaVersion": 1,
  "id": "alpha",
  "type": "store",
  "displayName": "Alpha Store",
  "store": { "host": "alpha.platform.com", "extraLinkHosts": ["shop.alpha.com"] },
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
    "adaptiveIconBackground": "#FFFFFF"
  },
  "push": { "enabled": false }
}
```

Unknown fields are errors (typo protection). `"$comment"` is allowed anywhere.

| Field | Required | Rule / meaning |
|---|---|---|
| `schemaVersion` | yes | `1` |
| `id` | yes | Equals the folder name. |
| `type` | yes | `store` = white-label app locked to `store.host`. `platform` = generic store browser (at most one). |
| `displayName` | yes | Home-screen name, ≤ 30 chars, no `< > & " \`. Android `app_name`; iOS `CFBundleDisplayName`. |
| `store.host` | store | Store domain the app is locked to (`TENANT_HOST`). Verified App Link / Universal Link host. Must resolve to an active store through `/resolve`. Unique across tenants. |
| `store.extraLinkHosts` | no | The store's custom domains: extra verified link hosts; links to them stay in the app. |
| `android.enabled` | yes | Build an Android app. |
| `android.applicationId` | if enabled | Full Play package id (`com.brand.shop`), unique, **permanent after the first upload**. Use the tenant's own reverse domain. |
| `android.urlScheme` | if enabled | Custom scheme for payment return / "Open in app" (`alphashop://`). Unique per tenant: two apps sharing one would steal each other's payment returns. Must equal `ios.urlScheme` when both are enabled; becomes the `APP_URL_SCHEME` dart-define. |
| `android.secretsEnvironment` | no | GitHub Environment holding this tenant's Android secrets (default `tenant-<id>-android`). |
| `ios.enabled` | yes | Build an iOS app. |
| `ios.bundleId` | if enabled | Explicit App ID (`com.brand.shop`), unique, **permanent after the first upload**. Must equal the App ID in the tenant's Apple account and the App Store Connect record. |
| `ios.urlScheme` | if enabled | Custom scheme (payment return), unique per tenant. Becomes the only `CFBundleURLSchemes` entry. |
| `ios.teamId` | release | 10-character Apple Team ID of the **tenant's** developer account (signing team, `DEVELOPMENT_TEAM`). |
| `ios.appStoreConnectAppId` | release + upload | Numeric Apple ID of the App Store Connect app record (string). TestFlight upload refuses without it. |
| `ios.sku` | release | SKU of the app record (`[A-Za-z0-9._-]`). |
| `ios.secretsEnvironment` | no | GitHub Environment with this tenant's Apple secrets (default `tenant-<id>-ios`). |

iOS native values derived from the manifest (no extra fields): Universal Links = `applinks:` + `webcredentials:`
for `store.host` and each `store.extraLinkHosts` entry (`*.<platformDomain>` for `platform`); display name =
`displayName` (no `//` or `$`, they break xcconfig/Info.plist); icon = `branding.appIcon` rendered into all 19 AppIcon
slots (`platform` keeps `ios/Runner/Assets.xcassets/AppIcon.appiconset`); signing = fastlane match profile
`match AppStore <bundleId>` of `ios.teamId`.
| `branding.appIcon` | store | 1024×1024 PNG, square, **no alpha channel** when iOS is enabled. Generates all Android launcher densities. Optional for `platform` (keeps `android/app/src/main/res` icons). |
| `branding.appIconForeground` | no | 1024×1024 PNG adaptive-icon foreground (keep artwork inside the centre 66%). Enables an Android adaptive icon. |
| `branding.adaptiveIconBackground` | no | `#RRGGBB`, adaptive-icon background (default `#FFFFFF`). |
| `branding.splash` | no | Accepted but **not used yet** (the native splash keeps the neutral mark; the Flutter splash shows the store name/logo). |
| `push.enabled` | no | Must stay `false` until Phase 7 (Firebase) is built; then `android/google-services.json` and `ios/GoogleService-Info.plist` go in this folder and are checked against the package / bundle id (`BUNDLE_ID`). The iOS build stages the plist and adds `aps-environment` only for that tenant; verify fails if another tenant's plist or a plist without push is bundled. |

In-app branding (colors, logo, theme, sections) is **not** here: it still comes from the store's
`GET /bootstrap`, so the store owner changes it in the admin without a new app release.

## Placeholders

Values containing `example` / `yourcompany` / `replace` segments, `*.example.com` / `.test` hosts, a
`REPLACE` display name or the unchanged `_template` icon are **warnings** in normal validation and
**errors** with `validate --release` / release builds. `platform` and `demo` currently use placeholders on
purpose (`com.yourcompany.storeapp`, `platform.example.com`, `com.demobrand.example.shop`).

## Secrets (never in this folder)

| Where | What |
|---|---|
| GitHub Environment `tenant-<id>-android` | `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS`, `ANDROID_KEY_PASSWORD`, `PLAY_SERVICE_ACCOUNT_JSON_BASE64` |
| GitHub Environment `tenant-<id>-ios` | `ASC_KEY_ID`, `ASC_ISSUER_ID`, `ASC_PRIVATE_KEY_P8_BASE64` (App Store Connect API key, App Manager), `MATCH_GIT_URL`, `MATCH_PASSWORD`, `MATCH_GIT_SSH_KEY` (the tenant's own match storage) |
| Local iOS build / upload | `ios/signing/<id>.env` (gitignored): `KEY=VALUE` lines `MATCH_GIT_URL`, `MATCH_PASSWORD`, `ASC_KEY_ID`, `ASC_ISSUER_ID`, and `ASC_PRIVATE_KEY_P8_BASE64` or `ASC_KEY_PATH` (`.p8` path, relative to that file; keep the `.p8` outside git, e.g. next to it). Or the environment with `IOS_SIGNING_TENANT=<id>` + the same names. |
| Local release build | `android/keystores/<id>.properties` (gitignored) with `storeFile`, `storePassword`, `keyAlias`, `keyPassword` (`storeFile` relative to that file), or env `ANDROID_SIGNING_TENANT=<id>` + `ANDROID_KEYSTORE_PATH` / `ANDROID_KEYSTORE_PASSWORD` / `ANDROID_KEY_ALIAS` / `ANDROID_KEY_PASSWORD`. The `platform` tenant also accepts the Flutter-standard `android/key.properties`. |

Release builds without the tenant's key **fail**; they are never signed with the debug key, and one
tenant's key is never used for another tenant (`ANDROID_SIGNING_TENANT` / `IOS_SIGNING_TENANT` must match).
`validate --release --platform ios` checks the iOS credentials are present (not that they work) before any build.

## Registering the app with providers (per tenant)

- **Google Play**: app record in the tenant's Play Console; first AAB uploaded manually (PUBLISH_TO_STORES.md §5, §7.1).
- **App Links**: enter `applicationId` + the Play app-signing (and upload) SHA-256 in the central admin → Plans →
  Registered Users → Edit store → **Mobile App**; the backend then lists them in `/.well-known/assetlinks.json` on
  `store.host` and every `extraLinkHosts` entry (each must be a registered domain of that store).
- **Apple**: Bundle ID with Associated Domains (+ Push only when push is on), App Store Connect record (→
  `ios.appStoreConnectAppId`, `ios.sku`), `ios.teamId`, API key, match storage (PUBLISH_TO_STORES.md §6).
- **Universal Links**: `ios.teamId` + `ios.bundleId` in the same **Mobile App** tab → `<teamId>.<bundleId>` in
  `/.well-known/apple-app-site-association` on `store.host` and every `extraLinkHosts` entry.
- **Payment return scheme**: `urlScheme` in the same tab (must equal `tenant.json`).
- **Firebase** (only when push is turned on): one Android app (package id) + one iOS app (bundle id) per tenant.
