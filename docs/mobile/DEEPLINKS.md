# Mobile deep links (P6)

Status: configuration and backend are implemented. NOTHING is verified on a device or against Google/Apple: that needs the
REQUIRED INPUTS below. Backend file output is covered by `tests/Feature/Mobile/MobileApiTest.php`.

## REQUIRED INPUTS (not provided yet - placeholders in use)
| Input | Where it goes |
|---|---|
| Platform domain (e.g. `platform.example.com`) | `application/android/gradle.properties` `app.platformDomain`; `application/ios/Flutter/AppIdentity.xcconfig` `APP_ASSOCIATED_DOMAIN` (`*.domain`) |
| Final Android package / iOS bundle id (placeholder `com.yourcompany.storeapp`) | `gradle.properties` `app.applicationId`; `AppIdentity.xcconfig` `APP_BUNDLE_ID`; backend `MOBILE_ANDROID_PACKAGE` |
| Android SHA-256 cert fingerprint(s) | backend `MOBILE_ANDROID_SHA256` (comma separated) |
| Apple Team ID | backend `MOBILE_IOS_APP_ID=<TeamID>.<BundleID>`; Xcode signing team |
| Custom scheme (default `storeapp`) | `gradle.properties` `app.urlScheme`, `AppIdentity.xcconfig` `APP_URL_SCHEME`, backend `MOBILE_DEEP_LINK_SCHEME` |
| Store URLs (optional) | backend `MOBILE_PLAY_STORE_URL`, `MOBILE_APP_STORE_URL` |

Change ids in ONE place per platform (the two files above). The Kotlin source package `com.example.application` is only the
`namespace`; it does not need to match the store id.

## What is wired
- Android: `AndroidManifest.xml` has an `autoVerify` https filter for `*.${platformDomain}` and a custom-scheme filter
  `${urlScheme}://` (payment result and landing "Open in app").
- iOS: `Runner/Runner.entitlements` (`applinks:` + `webcredentials:` for `$(APP_ASSOCIATED_DOMAIN)`), `Info.plist` URL scheme,
  `FlutterDeepLinkingEnabled=false` (app_links handles links), `CODE_SIGN_ENTITLEMENTS` set on Runner configs.
- Flutter: `lib/features/deeplink` (app_links) already parses https links by host, honours `?store=`, and custom-scheme
  links as `scheme://<store-host>/<path>`; `scheme://payment/...` is left to the payment WebView.
- Backend: `/.well-known/assetlinks.json`, `/.well-known/apple-app-site-association` (and `/apple-app-site-association`) from
  `config/mobile.php`; empty documents until env is set.
- Landing: `GET /app?path=/products/x` on every tenant domain (`AppLandingController`, standalone view
  `resources/views/storefront/app-landing.blade.php`, outside theme layouts/routes): Open in app (`scheme://<host>/<path>`),
  store buttons, continue in browser, and the store code for manual entry in the app. `path` is sanitised (no open redirect).

## DNS
Wildcard `*.platform.example.com` must resolve to the app server with a valid wildcard TLS cert, and serve the two
well-known files over HTTPS with no redirect, `application/json`, publicly (no auth).

## Signing fingerprints (Android)
- Debug: `keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android` (dev only).
- Release with Play App Signing: use the "App signing key certificate" SHA-256 from Play Console > App integrity (NOT your
  upload key). List the upload key too if you sideload upload-signed builds. Put all in `MOBILE_ANDROID_SHA256`.
- Note: `build.gradle.kts` release still uses the debug signing config (template TODO); set a real one before release.

## Apple Team ID
Apple Developer > Membership. `MOBILE_IOS_APP_ID = <TeamID>.<BundleID>`. Enable the Associated Domains capability on the App ID.
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
