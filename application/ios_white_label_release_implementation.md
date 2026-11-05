# iOS White-Label Build & Release Management

## Implementation Brief for Claude Sonnet 5.5

**Project context:** Existing Flutter multi-vendor / multi-tenant application  
**Target outcome:** One shared Flutter codebase that produces separately branded iOS applications for individual store tenants.  
**Document date:** 5 November 2026  
**Execution mode:** Inspect the current repository first, then implement this specification in the existing project.

---

## 1. Mission

Extend the existing Flutter application and its tenant build tooling with a production-ready **iOS White-Label Build & Release Management System**.

The business model is:

- There is one shared Flutter/Dart codebase.
- Each tenant/store receives its own independent iOS application.
- Every tenant application has its own Bundle ID, App Store Connect app record, app name, icon, branding, Firebase iOS configuration, signing identity, and release history.
- All tenant apps share the same application source code and feature implementation.
- A code change is implemented once in the shared codebase. The build system can then rebuild and upload the updated version for one tenant or for all eligible tenants.
- Each tenant's application is published under that tenant's own Apple Developer / App Store Connect account whenever the commercial model and App Review requirements call for the customer to own and submit the app.

**This is not a request to build a multi-tenant runtime inside the mobile app.** Each installed app is a standalone store application. Tenant-specific values are injected at build time.

Do not create a new Flutter project. Do not rewrite existing screens, APIs, state management, routing, or business logic unless a small change is strictly required for configuration injection.

---

## 2. Non-Negotiable Engineering Rules

1. **Inspect before changing.** Audit the existing Flutter app, Android flavors, tenant manifest/schema, build generator, Firebase integration, iOS project, Podfile, entitlements, plugins, URL schemes, deep links, and CI workflows.
2. **Preserve the working app.** The currently working tenant/application must remain buildable and must be represented as a tenant configuration rather than being replaced with dummy data.
3. **Integrate with the existing tenant generator.** Do not create a second, conflicting tenant registry or duplicate build CLI. Extend the existing tool if one already exists.
4. **Do not guess native settings.** Discover the actual Xcode target, scheme, configuration names, Runner project structure, minimum iOS version, plugin requirements, and signing mode before editing.
5. **No cross-tenant leakage.** A Store A build must never contain Store B's icon, display name, Bundle ID, Firebase plist, URL scheme, entitlements, API endpoint, or signing profile.
6. **No secrets in Git.** Never commit `.p8`, `.p12`, passwords, provisioning profiles, private keys, or raw CI credentials.
7. **Do not automatically submit for App Review.** The default automated action is build + upload to TestFlight. App Review submission and public release must require an explicit customer-controlled/manual approval step.
8. **No silent destructive edits.** Back up and document changes to `ios/Runner.xcodeproj/project.pbxproj`, Info.plist, entitlements, Podfile, and build phases. Keep changes minimal and reproducible.
9. **Fail closed.** Missing Bundle ID, Team ID, App Store Connect app ID, signing inputs, icon assets, or Firebase configuration must stop the build with a useful error. Never fall back to another tenant's credentials.
10. **Use isolated build workspaces.** If files are staged or generated into the iOS project before compiling, build each tenant in an isolated temporary workspace or clean checkout. Do not allow parallel builds to overwrite each other's generated files.

---

## 3. Recommended iOS Architecture

### 3.1 Important Flutter / Xcode distinction

Android has `productFlavors`. Xcode does not have a feature with that exact name; Flutter iOS flavors are implemented through **Xcode schemes and build configurations**.

For a platform that may eventually have many tenant apps, do not automatically create hundreds of permanent Xcode schemes unless the existing project architecture genuinely requires it.

**Preferred scalable approach:**

- Keep a small number of Xcode schemes for build environments, for example `development` and `production`.
- Use the shared `production` scheme to build any tenant's production iOS app.
- Generate a tenant-specific iOS build configuration before each build. This configuration supplies the tenant's Bundle ID, display name, icon, Firebase plist, entitlements, URL schemes, and other native settings.
- Pass the tenant's non-secret Dart configuration through `--dart-define-from-file` or the project's existing configuration mechanism.
- Run each tenant build in an isolated workspace so generated files cannot leak across builds.

If the existing repository already has a robust per-tenant scheme generator, inspect it and preserve it if it is maintainable. Do not create a second scheme strategy beside it. Document the chosen strategy and why it scales.

### 3.2 Suggested iOS build flow

```text
Shared Flutter / Dart Codebase
             |
             v
      Tenant Manifest
             |
             v
       Tenant Builder
             |
             +--> Validate tenant metadata
             +--> Resolve customer Apple account credentials
             +--> Generate tenant xcconfig / plist / entitlements
             +--> Stage AppIcon.appiconset
             +--> Stage GoogleService-Info.plist
             +--> Stage Dart compile-time config
             |
             v
     Isolated macOS Build Workspace
             |
             v
      Flutter + Xcode Archive
             |
             v
        Signed IPA Artifact
             |
             v
   Fastlane / App Store Connect API
             |
             v
    Customer's App Store Connect App
             |
             v
       TestFlight Processing
             |
             v
   Customer Review / Manual Submission
```

---

## 4. Tenant iOS Configuration

Extend the existing tenant manifest. The names below are illustrative; adapt them to the repository's current schema rather than introducing duplicate field names.

### 4.1 Example tenant manifest

```yaml
id: store_alpha
name: Store Alpha
status: active

app:
  display_name: Store Alpha
  bundle_id: com.example.storealpha
  marketing_version: 1.0.0
  ios_build_number: 1
  api_base_url: https://api.storealpha.example

branding:
  logo: tenants/store_alpha/assets/logo.png
  primary_color: '#E91E63'

ios:
  enabled: true
  scheme: production
  team_id: ABC1234567
  app_store_connect_app_id: '1234567890'
  sku: store-alpha-ios-001
  app_icon: tenants/store_alpha/ios/AppIcon.appiconset
  firebase_plist: tenants/store_alpha/ios/GoogleService-Info.plist
  entitlements: tenants/store_alpha/ios/Runner.entitlements
  info_plist_overrides: tenants/store_alpha/ios/Info.plist
  signing:
    method: manual
    certificate_secret_ref: STORE_ALPHA_IOS_DISTRIBUTION_P12
    certificate_password_secret_ref: STORE_ALPHA_IOS_P12_PASSWORD
    provisioning_profile_secret_ref: STORE_ALPHA_IOS_APPSTORE_PROFILE
  app_store_connect:
    api_key_id_secret_ref: STORE_ALPHA_ASC_KEY_ID
    api_issuer_id_secret_ref: STORE_ALPHA_ASC_ISSUER_ID
    api_private_key_secret_ref: STORE_ALPHA_ASC_P8_BASE64
  release:
    upload_target: testflight
    submit_for_review: false
    auto_release: false
```

### 4.2 Public configuration vs secrets

The tenant manifest may contain non-secret identifiers such as:

- Tenant ID and display name.
- Bundle ID.
- Apple Team ID.
- App Store Connect numeric App ID.
- SKU.
- Marketing version and configured build-number policy.
- Asset paths.
- API base URL, if this is a public client endpoint.
- Firebase plist path, provided the repository's security policy permits it.
- Required app capabilities and entitlement values.

The manifest must contain **secret references only**, never secret values. Resolve the referenced values from GitHub Environment Secrets, an approved secrets manager, or a dedicated encrypted signing store.

Do not put the following in Git:

- App Store Connect `.p8` private key.
- Distribution `.p12` certificate.
- Certificate password.
- `.mobileprovision` file, unless it is stored in an approved encrypted signing vault.
- Apple Account password or 2FA codes.
- Match encryption password or private signing repository credentials.

### 4.3 Required validation

The tenant validator must verify at least:

- Tenant ID is unique and safe for paths and CLI arguments.
- Bundle ID is a valid explicit identifier and unique across tenants where required.
- Team ID is present for an enabled iOS tenant.
- App Store Connect App ID is present before upload (a build-only workflow can allow it to be absent only if explicitly configured).
- SKU is present for the App Store Connect app record/onboarding checklist.
- Display name is non-empty and within Apple's platform constraints.
- App icon source exists and has the required image dimensions/format.
- Firebase plist exists if Firebase is enabled in this app.
- Entitlements and URL schemes are valid and tenant-specific.
- Secret references resolve in the selected CI environment before signing/upload.
- The generated Bundle ID matches the App ID and App Store Connect app record configuration.
- No other tenant's asset or credential path is referenced.

---

## 5. Native iOS Project Implementation

### 5.1 Bundle ID and display name

Configure the Xcode Runner target so the Bundle ID is derived from a generated build setting, for example:

```text
PRODUCT_BUNDLE_IDENTIFIER = $(APP_BUNDLE_ID)
```

Set the display name through a build setting referenced by the app's Info.plist, for example:

```text
CFBundleDisplayName = $(APP_DISPLAY_NAME)
```

The exact location and syntax must match the current Xcode project. Do not blindly insert duplicate keys. Verify the final built app's `CFBundleIdentifier` and `CFBundleDisplayName` from the generated IPA.

### 5.2 Generated xcconfig

Use a generated tenant configuration file, for example:

```text
ios/Flutter/Tenant.generated.xcconfig
```

or a generated file in an isolated build directory. Include it from the appropriate base configuration(s) without breaking Flutter's `Generated.xcconfig` inclusion.

Example values:

```xcconfig
APP_BUNDLE_ID = com.example.storealpha
APP_DISPLAY_NAME = Store Alpha
APP_ICON_ASSET_NAME = AppIcon
TENANT_ENTITLEMENTS_PATH = Runner/Tenants/store_alpha/Runner.entitlements
```

The generator must write this file immediately before the build and must not leave stale tenant values in the shared working tree. Prefer an isolated workspace with a clean generated file for each build.

### 5.3 App icon

- Stage the selected tenant's iOS AppIcon asset catalog into the correct Runner asset catalog location.
- Keep `ASSETCATALOG_COMPILER_APPICON_NAME` consistent with the actual app icon set name.
- Validate the icon set's `Contents.json` and all referenced images before compiling.
- Reject invalid/missing icons rather than silently reusing the default app icon.
- After build, inspect the compiled app/IPA to ensure the expected icon was included.

### 5.4 Firebase and push notifications

If the current app uses Firebase:

- Stage the correct tenant-specific `GoogleService-Info.plist` into the built iOS app bundle.
- Verify the Firebase app's Bundle ID matches the tenant's iOS Bundle ID.
- Ensure the correct Firebase project is selected for each tenant.
- Preserve the existing FlutterFire initialization strategy. Do not introduce a second Firebase initialization mechanism.
- Configure APNs credentials for each relevant Firebase project/team and validate push notifications on a real device.
- Never fall back to another tenant's Firebase plist if the selected file is missing.

If Firebase is not used by the current application, do not add it just for this implementation.

### 5.5 Entitlements and capabilities

Tenant configuration may require different capabilities, including (only when actually used):

- Push Notifications.
- Associated Domains.
- Sign in with Apple.
- App Groups.
- Keychain Sharing.
- Background Modes.
- Apple Pay or other approved capabilities.

Requirements:

- Generate or stage a tenant-specific entitlements file.
- The client's Apple Developer account must have the matching App ID capabilities enabled.
- The provisioning profile must correspond to the exact App ID and signing certificate.
- Validate that the signed app's entitlements do not exceed or conflict with the provisioning profile.
- Do not enable capabilities that the app does not use.

### 5.6 URL schemes, Universal Links, and domains

Inspect existing plugins and native configuration for:

- OAuth callback URL schemes.
- Payment provider callback schemes.
- Deep links.
- Universal Links / Associated Domains.
- Custom URL types.

Generate or stage tenant-specific values where needed. Do not hardcode one store's domain or callback URL into the shared native project. Test each configured link on a physical device.

### 5.7 CocoaPods and Flutter plugins

- Preserve the existing Podfile and Flutter plugin integration.
- Confirm all plugins used by the app support the project's pinned Flutter and Xcode versions.
- Run `pod install` in the build workspace.
- Do not run `pod update` as a routine build step because it can unexpectedly change dependency versions.
- Keep `Podfile.lock` consistent and committed where appropriate.

---

## 6. Apple Account and App Ownership Model

### 6.1 Recommended ownership

For the intended white-label commercial service, the recommended default is:

- Each customer owns their Apple Developer Program membership and App Store Connect account.
- Each customer owns their app record, Bundle ID, signing identity, app metadata, TestFlight testers, and public release decision.
- The customer invites the delivery/development team with only the permissions needed to build and upload the app.
- The customer's account credentials and signing materials remain isolated from every other customer.

Do not ask for or store a customer's Apple Account password. Use delegated team access and App Store Connect API keys.

### 6.2 Client onboarding checklist

For each tenant, the customer (or authorized account holder) should:

1. Enroll in the Apple Developer Program under the appropriate individual or organization identity.
2. Complete any legal agreements and account setup required by Apple.
3. Create an explicit App ID using the exact Bundle ID required for that tenant, for example `com.example.storealpha`.
4. Enable only the capabilities the app actually needs.
5. Create the app record in App Store Connect with the matching Bundle ID and a unique SKU.
6. Invite the approved development team member(s) through Users and Access and assign the least-privilege roles needed.
7. Provide or authorize a secure signing setup for the CI pipeline.
8. Create an App Store Connect API key for CI upload and share its credentials securely.
9. Complete app metadata, privacy disclosures, screenshots, support URL, privacy policy URL, age rating, review contact, and review notes.
10. Review the uploaded build through TestFlight and take responsibility for the App Review submission and release decision.

**Important:** App Store Connect roles and Apple Developer account resource permissions are not identical. Access to Certificates, Identifiers & Profiles may require additional team permissions. Confirm the least-privilege access needed for the chosen signing workflow with the customer's account holder.

### 6.3 App ID, Bundle ID, and App Store Connect record

These values must agree:

- Tenant manifest `bundle_id`.
- Explicit App ID registered in the customer's Apple Developer account.
- Xcode `PRODUCT_BUNDLE_IDENTIFIER` in the built app.
- Bundle ID selected for the App Store Connect app record.
- Firebase iOS app Bundle ID, if Firebase is enabled.

Apple uses the Bundle ID and version information embedded in the uploaded binary to associate the build with the relevant app/version record. Do not attempt to upload the first build before the app record exists.

---

## 7. Signing Strategy

### 7.1 Separate signing from App Store Connect authentication

There are two different concerns:

1. **Code signing:** Distribution certificate + private key + App Store provisioning profile.
2. **Upload authentication:** App Store Connect API key (`.p8`, Key ID, Issuer ID) used by Fastlane/Transporter to communicate with App Store Connect.

One does not replace the other. The CI pipeline needs a valid signing arrangement to create the IPA and valid App Store Connect credentials to upload it.

### 7.2 Preferred CI options

Implement one supported strategy and document it. Do not mix two strategies without a clear reason.

**Option A - Fastlane Match (preferred when account access and operations support it):**

- Keep each customer's signing assets in a separate encrypted Match repository or separate secure storage namespace.
- Use a tenant-specific Team ID and App Identifier.
- Use read-only Match mode in CI.
- Keep the Match password and repository credentials in that tenant's CI environment.
- Never share a signing repository, certificate, or provisioning profile between unrelated customer Apple teams.

**Option B - Customer-provided manual signing assets:**

- Customer/account holder creates or authorizes an Apple Distribution certificate and matching App Store Connect provisioning profile.
- Securely export the certificate as `.p12` with a strong password.
- Store the `.p12`, password, and `.mobileprovision` as encrypted CI secrets or in a secure secrets vault.
- Import them into a temporary macOS keychain during the build.
- Remove temporary keychain/profile material at the end of the job, including on failure.

Automatic signing may be used for a manual first-time setup if appropriate, but CI must be deterministic and non-interactive. Do not depend on an engineer manually signing into Xcode during every build.

### 7.3 Signing isolation and cleanup

- Resolve credentials only after the tenant is selected and validated.
- Never print private key material or decoded secrets in logs.
- Use temporary keychains with random per-job names.
- Clean up keychains, profiles, and decoded files in an `if: always()` cleanup step.
- Prevent two builds for the same tenant from racing over signing assets or build-number allocation.
- Do not persist signing assets in the build artifact.

---

## 8. App Store Connect API Key

For each customer account, create an App Store Connect API key intended for CI automation.

Store the following as customer-specific secrets:

- `ASC_KEY_ID`
- `ASC_ISSUER_ID`
- `ASC_PRIVATE_KEY_P8_BASE64` (or a secure file secret)

Also store the app-specific non-secret value in the tenant manifest:

- `app_store_connect_app_id`

Use the App Store Connect API key with Fastlane's supported API-key authentication. This avoids relying on an interactive Apple ID login and 2FA prompt during every upload.

Security requirements:

- Treat the `.p8` file as a private key. It should be downloaded once, stored securely, and never committed.
- Use a separate key for each customer's account where practical.
- Use the least role/access scope that supports the required upload workflow.
- Do not expose the key to pull requests from forks or untrusted workflows.
- Rotate/revoke keys when a customer relationship ends or compromise is suspected.

---

## 9. Build Number and Version Management

Maintain versioning per tenant application.

- `CFBundleShortVersionString` is the user-visible marketing version, e.g. `1.0.0`.
- `CFBundleVersion` is the build number and must increase for subsequent uploads of the same app/version line as required by App Store Connect.
- Store the last known build number per tenant, or query the latest uploaded build through Fastlane/API before allocating the next number.
- Use a concurrency lock per tenant to prevent duplicate build numbers.
- Never use one global build counter without tenant awareness.
- If the App Store Connect lookup fails, fail safely or require an explicit override; do not silently guess a number that may already have been uploaded.

Example:

```text
Store Alpha: version 1.0.0 / build 18
Store Beta:  version 1.0.0 / build 7
Store Gamma: version 1.2.0 / build 31
```

The same shared code commit can produce different tenant build numbers.

---

## 10. Build Commands and Artifacts

Adapt the exact command syntax to the Flutter version pinned in the repository and to the selected Xcode scheme/configuration.

### 10.1 Example Flutter IPA build

If the project uses one shared production scheme:

```bash
flutter build ipa \
  --release \
  --flavor production \
  --build-name 1.0.0 \
  --build-number 18 \
  --dart-define-from-file=build/tenant_config/store_alpha.dart-define.json \
  --export-method app-store
```

If the project uses a checked-in export options plist, use the appropriate `--export-options-plist` option instead of `--export-method`; do not pass conflicting export configuration options together.

The command above is illustrative. Verify the actual scheme name and Flutter CLI support in the repository's pinned Flutter SDK before implementing it.

Expected IPA output is normally under:

```text
build/ios/ipa/*.ipa
```

### 10.2 Example build-only command in the tenant CLI

Implement an equivalent command using the repository's existing CLI style:

```bash
tenant-builder validate --tenant store_alpha --platform ios

tenant-builder generate --tenant store_alpha --platform ios

tenant-builder build --tenant store_alpha --platform ios --artifact ipa --mode release
```

### 10.3 Example TestFlight upload command

```bash
tenant-builder upload \
  --tenant store_alpha \
  --platform ios \
  --destination testflight
```

### 10.4 Build all tenants

```bash
tenant-builder build --all --platform ios --artifact ipa --mode release

tenant-builder upload --all --platform ios --destination testflight
```

The `--all` flow must:

- Enumerate only active tenants with `ios.enabled: true`.
- Validate each tenant independently.
- Use a separate environment/credential set per tenant.
- Build in an isolated workspace.
- Track each tenant result separately.
- Continue or stop according to an explicit failure policy; never upload an incorrectly configured tenant.
- Produce a final summary listing successes, failures, skipped tenants, artifact paths, versions, build numbers, and upload status.

---

## 11. Fastlane Integration

Use the existing Fastlane setup if one is present. Otherwise, add a small, maintainable Fastlane configuration with pinned Ruby/Bundler dependencies.

### 11.1 Expected responsibilities

- Load a tenant's App Store Connect API key at runtime.
- Upload the already signed IPA to the correct App Store Connect app / TestFlight.
- Log the tenant ID, Bundle ID, marketing version, build number, and upload result without logging secrets.
- Optionally distribute to an internal TestFlight group only when configured.
- Do not submit for App Review or release automatically by default.

### 11.2 Illustrative Fastlane lane

Adapt to the Fastlane version and existing repository conventions:

```ruby
platform :ios do
  desc "Upload a signed tenant IPA to TestFlight"
  lane :upload_testflight do |options|
    api_key = app_store_connect_api_key(
      key_id: ENV.fetch("ASC_KEY_ID"),
      issuer_id: ENV.fetch("ASC_ISSUER_ID"),
      key_content: ENV.fetch("ASC_PRIVATE_KEY_P8_BASE64"),
      is_key_content_base64: true,
      duration: 1200,
      in_house: false
    )

    pilot(
      ipa: options.fetch(:ipa),
      app_identifier: options.fetch(:bundle_id),
      api_key: api_key,
      skip_waiting_for_build_processing: true,
      distribute_external: false
    )
  end
end
```

Validate the exact Fastlane API and parameter names against the version installed in the project. The lane must fail if any required credential or tenant identifier is missing.

### 11.3 Submission and release

Keep these actions separate:

- **Build IPA** - compile and sign.
- **Upload TestFlight** - upload binary to App Store Connect.
- **Submit for Review** - separate action with explicit approval and complete metadata.
- **Release to App Store** - separate action, controlled by the app owner/customer.

Do not combine all four into one default `deploy` command.

---

## 12. GitHub Actions on macOS

The build job must run on a macOS runner because iOS archive/export requires Xcode.

### 12.1 Runner selection

- Inspect the app's current Xcode, Flutter, CocoaPods, and Swift requirements.
- Select and pin a compatible GitHub-hosted macOS/Xcode runner label.
- Do not automatically move the project to a new major Xcode release just because GitHub offers it.
- Log the actual Xcode, Flutter, Ruby, Bundler, and CocoaPods versions in the job output.
- Revisit runner labels when GitHub retires images or updates Xcode availability.

### 12.2 Workflow behavior

Create a workflow similar to:

```text
.github/workflows/ios-tenant-release.yml
```

Inputs:

- `tenant_id` (required for single-tenant mode).
- `all_tenants` (optional boolean; mutually exclusive with a specific tenant).
- `action`: `build` or `upload_testflight`.
- `marketing_version` (optional override if allowed by release policy).
- `build_number` (optional controlled override; otherwise allocate safely).

Workflow stages:

1. Validate input and tenant is active / iOS-enabled.
2. Checkout the shared codebase at a specific commit SHA.
3. Set up the repository-pinned Flutter SDK.
4. Set up Ruby and install the locked Fastlane dependencies with Bundler.
5. Set up Xcode and CocoaPods.
6. Resolve secrets from the selected tenant's GitHub Environment or external secret manager.
7. Create an isolated workspace for the tenant.
8. Run tenant validation and generate native iOS configuration.
9. Install pods without changing locked dependency versions unexpectedly.
10. Install/import signing assets in a temporary keychain.
11. Build and sign the IPA.
12. Inspect the IPA metadata and confirm the Bundle ID/version/build number.
13. Upload to the tenant's App Store Connect app only when `action=upload_testflight`.
14. Save IPA and build logs as an artifact, without secrets or signing material.
15. Clean up temporary keychains, profiles, generated secret files, and temporary workspaces.
16. Publish a structured job summary.

### 12.3 GitHub Environments

Use a separate GitHub Environment for each customer/tenant, for example:

```text
tenant-store-alpha-ios
tenant-store-beta-ios
```

Each environment should hold only that tenant's signing and App Store Connect secrets. Configure environment protection rules/approvals where appropriate.

An alternative is an external secrets manager using short-lived access from CI. Do not create a single plaintext JSON file containing all customers' secrets.

### 12.4 Concurrency

Use a concurrency group per tenant and platform, for example:

```text
ios-release-store-alpha
```

This prevents parallel jobs for the same app from allocating the same build number or modifying shared tenant state. Different tenants may build concurrently only when each job uses an isolated workspace and isolated credentials.

---

## 13. Customer App Store Connect Setup and First Release

This section is also the operational checklist that must be included in the project documentation.

### 13.1 One-time setup for each tenant

1. Customer has an active Apple Developer Program membership.
2. Customer registers an explicit App ID matching the tenant's Bundle ID.
3. Customer enables the app capabilities actually used by the app.
4. Customer creates a new iOS app record in App Store Connect.
5. The app record uses the exact Bundle ID and a unique SKU.
6. Customer invites the authorized development/release operator and grants only the necessary role and app access.
7. Customer creates/authorizes the signing certificate and App Store provisioning profile, or approves the selected Match workflow.
8. Customer creates an App Store Connect API key for CI and securely provides the Key ID, Issuer ID, and `.p8` private key.
9. The tenant manifest is completed and validated.
10. The iOS app is built and uploaded to TestFlight.

### 13.2 App Store Connect metadata checklist

Before the first public submission, confirm the app record has:

- App name and subtitle.
- Description and keywords where applicable.
- Primary and secondary category as appropriate.
- App icon and required screenshots for supported device families.
- Support URL.
- Privacy Policy URL.
- App Privacy disclosures.
- Age rating questionnaire.
- Export compliance answers where applicable.
- Content rights declarations where applicable.
- Review contact information.
- Review notes and working demo credentials if login is required.
- A working production backend and complete user journey for review.
- Any required in-app purchase configuration and review notes.

The customer should test the app on real devices through TestFlight, verify notifications/login/payments/deep links as applicable, then make the App Review submission and release decision.

---

## 14. Apple Review Policy - Critical Product Risk

**A successful IPA build or TestFlight upload does not mean Apple will approve a separate white-label app.**

Apple's App Review Guidelines include:

- Guideline **4.2.6**: apps created from commercialized templates or app-generation services may be rejected unless submitted directly by the provider of the app's content. Template providers should not submit apps on behalf of clients and should provide tools for clients to create customized, innovative apps with unique customer experiences.
- Guideline **4.3**: repeated submissions of apps that are essentially the same can be rejected; Apple may encourage combining them into one app.

Therefore:

- Separate Bundle IDs and different icons alone do not guarantee approval.
- Each tenant app must have a legitimate, customer-specific purpose, content, brand authorization, and user experience.
- Do not position the service as mass-producing identical thin wrappers around the same website.
- Customers should own their apps and participate directly in app creation, App Store Connect setup, review submission, and release.
- Before scaling to dozens/hundreds of apps, validate the model with Apple and review the actual app differences and business justification.
- Keep a fallback product strategy available if Apple requires a single app with store selection or another aggregated/picker model.

This is a product/compliance gate, not a coding issue. Do not promise customers App Store approval.

---

## 15. Test and Quality Assurance Requirements

Implement automated checks and a manual test checklist.

### 15.1 Tenant isolation tests

Build at least two sample tenants with intentionally different values:

- Different Bundle IDs.
- Different display names.
- Different icons.
- Different Firebase project/plist values if Firebase is enabled.
- Different API base URLs.
- Different URL schemes/domains if supported.
- Different signing Team IDs/profiles where test credentials are available.

Verify the resulting IPA for each tenant contains only its own values.

### 15.2 Native artifact verification

After building each IPA, inspect the IPA archive and confirm:

- `CFBundleIdentifier` equals the selected tenant Bundle ID.
- `CFBundleDisplayName` equals the tenant display name.
- Marketing version and build number are correct.
- Correct app icon assets were compiled.
- Correct Firebase plist is bundled (if applicable).
- Expected URL schemes and associated domains are present.
- Signed entitlements match expected capabilities.
- The signature and embedded provisioning profile match the selected team and app identifier.

### 15.3 Functional tests on a real device

At minimum, validate:

- Fresh installation and launch.
- Login, registration, logout, and password/OTP flows used by the app.
- API requests go to the correct tenant backend.
- Push notification registration and receipt.
- Universal links / deep links / OAuth / payment callbacks, if used.
- Camera, location, photo library, tracking, Bluetooth, or other permission prompts used by the app.
- No Store A branding, URLs, notification labels, or data appear in Store B.

### 15.4 Negative tests

Confirm the build fails clearly when:

- Bundle ID is missing/invalid.
- App Store Connect App ID is missing for upload.
- Team ID is missing.
- Signing profile does not match the Bundle ID.
- API key is missing or invalid.
- App icon is missing/invalid.
- Firebase plist belongs to another Bundle ID.
- A tenant is inactive or iOS is disabled.
- The allocated build number is not greater than the last accepted build number.

---

## 16. Expected CLI Interface

Use the existing CLI naming style if already implemented. The following is the desired behavior, not a requirement to duplicate these exact names.

```bash
# Validate one tenant's iOS setup
tenant-builder validate --tenant store_alpha --platform ios

# Generate tenant-specific native configuration
tenant-builder generate --tenant store_alpha --platform ios

# Build a signed IPA only
tenant-builder build --tenant store_alpha --platform ios --artifact ipa --mode release

# Upload the IPA to TestFlight
tenant-builder upload --tenant store_alpha --platform ios --destination testflight

# Build all active iOS tenants
tenant-builder build --all --platform ios --artifact ipa --mode release

# Upload all eligible tenants to their own TestFlight apps
tenant-builder upload --all --platform ios --destination testflight
```

The tool must clearly distinguish `build`, `upload_testflight`, `submit_for_review`, and `release`. The latter two must require explicit approval and must not run by default.

---

## 17. Required Deliverables from This Task

Claude must implement the system in the current repository and provide:

1. **Repository audit summary** identifying the current Flutter, Xcode, iOS project, Firebase, signing, and CI architecture.
2. **Tenant schema changes** integrated with the existing tenant manifest.
3. **iOS native configuration generator** for Bundle ID, display name, app icon, Firebase plist, entitlements, and tenant-specific native values.
4. **iOS IPA build command** integrated with the current tenant CLI.
5. **Signing integration** using either Fastlane Match or secure manual signing, with tenant isolation and cleanup.
6. **App Store Connect API integration** for TestFlight upload using each tenant's own credentials.
7. **GitHub Actions macOS workflow** supporting one tenant and all eligible tenants.
8. **Build-number management** per tenant.
9. **Artifact verification tests** and tenant isolation tests.
10. **Sample tenant configuration** with placeholder values, not real credentials.
11. **Customer onboarding guide** covering Apple Developer enrollment, Bundle ID, App Store Connect app record, user invitation, signing setup, API key, TestFlight, review, and release.
12. **Developer documentation** with exact commands and troubleshooting steps.
13. **Security checklist** confirming no secrets are committed or leaked in logs/artifacts.
14. **A final implementation report** listing changed files, commands run, successful tests, known blockers, and any manual customer actions still required.

Do not mark the task complete merely because a YAML file or GitHub Actions placeholder was created. At minimum, produce a working local/macOS build path and a validated CI workflow configuration. If actual Apple signing credentials are unavailable, implement the complete secret-driven flow and run all possible non-signing checks; clearly list the credentials/customer actions required for a real signed upload.

---

## 18. Suggested Repository Structure

Adapt this to the existing project rather than moving large parts of the application unnecessarily.

```text
project-root/
+-- lib/
|   +-- ... existing Flutter application ...
+-- tenants/
|   +-- store_alpha/
|   |   +-- tenant.yaml
|   |   +-- ios/
|   |       +-- AppIcon.appiconset/
|   |       +-- GoogleService-Info.plist
|   |       +-- Runner.entitlements
|   |       +-- Info.plist
|   +-- store_beta/
|       +-- ...
+-- tools/
|   +-- tenant_builder/
|       +-- ... existing tenant builder ...
|       +-- ios/
|           +-- config_generator.*
|           +-- artifact_verifier.*
|           +-- signing_helper.*
+-- ios/
|   +-- Runner.xcodeproj/
|   +-- Runner.xcworkspace/
|   +-- Flutter/
|   |   +-- Debug.xcconfig
|   |   +-- Release.xcconfig
|   |   +-- ...
|   +-- fastlane/
|       +-- Fastfile
|       +-- Appfile
+-- .github/
|   +-- workflows/
|       +-- ios-tenant-release.yml
+-- fastlane/ or ios/fastlane/
|   +-- ... use one canonical location only ...
+-- docs/
    +-- ios-white-label-release.md
```

Do not duplicate Fastlane directories if one already exists. Keep generated tenant configuration and temporary signing material out of version control.

---

## 19. Implementation Order

Implement in this order to reduce risk:

1. Audit the current app and existing tenant generator.
2. Define and validate the iOS tenant schema.
3. Make the current/default app build correctly through the new tenant configuration.
4. Implement dynamic Bundle ID and display name.
5. Implement icon and Firebase plist staging.
6. Implement tenant-specific entitlements, URL schemes, and domains where required.
7. Implement isolated signed IPA builds.
8. Implement per-tenant build numbering.
9. Implement App Store Connect API-key loading and TestFlight upload.
10. Add GitHub Actions macOS workflow and tenant environments.
11. Add multi-tenant build/upload orchestration.
12. Add artifact inspection, negative tests, and documentation.
13. Run regression checks to prove the existing app still behaves exactly as before.

After each significant stage, run relevant tests and report results. Do not defer all testing until the end.

---

## 20. Definition of Done

This task is complete only when all applicable items below are satisfied:

- [ ] Existing Flutter app remains functional.
- [ ] The current tenant can be built as an iOS app through the tenant builder.
- [ ] A second tenant can be built with a different Bundle ID and branding without source-code duplication.
- [ ] Display name and app icon are tenant-specific.
- [ ] Firebase plist and push notification configuration are tenant-specific, if Firebase is used.
- [ ] Tenant-specific entitlements and URL schemes are applied correctly where required.
- [ ] IPA Bundle ID, display name, version, build number, icon, and signing metadata are verified automatically.
- [ ] Signing material is retrieved securely and cleaned up after builds.
- [ ] App Store Connect API credentials are isolated per customer account.
- [ ] A signed IPA can be uploaded to the matching customer's TestFlight app when credentials are provided.
- [ ] A single-tenant build/upload workflow works.
- [ ] An all-tenants workflow works with isolated builds and per-tenant reporting.
- [ ] App Review submission and public release are not triggered automatically.
- [ ] No customer secrets are committed or printed in CI logs.
- [ ] Documentation explains the customer's one-time Apple setup and the recurring release workflow.
- [ ] Apple policy risk under Guidelines 4.2.6 and 4.3 is explicitly documented for the business owner.

---

## 21. Official References

Use the current official documentation while implementing. Do not rely on old tutorials if the current Flutter/Xcode/Fastlane version has changed.

1. Flutter - Set up flavors for iOS and macOS:  
   https://docs.flutter.dev/deployment/flavors-ios
2. Apple - App Review Guidelines (especially 4.2.6 and 4.3):  
   https://developer.apple.com/app-store/review/guidelines/
3. Apple - Register an App ID:  
   https://developer.apple.com/help/account/identifiers/register-an-app-id/
4. Apple - Create an App Store Connect provisioning profile:  
   https://developer.apple.com/help/account/provisioning-profiles/create-an-app-store-provisioning-profile
5. Apple - Add and edit App Store Connect users:  
   https://developer.apple.com/help/app-store-connect/manage-your-team/add-and-edit-users
6. Apple - Upload builds:  
   https://developer.apple.com/help/app-store-connect/manage-builds/upload-builds
7. Apple - View build processing status and metadata:  
   https://developer.apple.com/help/app-store-connect/manage-builds/view-builds-and-metadata
8. Fastlane - pilot / TestFlight upload:  
   https://docs.fastlane.tools/actions/pilot/
9. Fastlane - App Store Connect API:  
   https://docs.fastlane.tools/app-store-connect-api/
10. GitHub - Actions runner images and available macOS/Xcode versions:  
    https://github.com/actions/runner-images

---

## Final Instruction to Claude

Start by inspecting the repository. Then implement this iOS release-management layer in the existing Flutter app and existing tenant build system. Make safe, minimal changes, preserve current application behavior, and validate the result with actual builds/tests wherever the environment allows.

Do not stop after producing a plan. Execute the implementation. If a real customer Apple account, certificate, provisioning profile, or API key is unavailable, complete the code and CI integration using secret references/placeholders, run all possible validation steps, and clearly state the exact manual onboarding items needed before the first signed TestFlight upload.
