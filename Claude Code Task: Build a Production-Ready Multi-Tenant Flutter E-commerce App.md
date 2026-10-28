# Claude Code Task: Build a Production-Ready Multi-Tenant Flutter E-commerce App

## 1. Your Role

Act as a **Senior Flutter Engineer, Mobile Architect, Laravel API Integration Engineer, and QA Engineer**.

Your task is to build a production-ready Flutter mobile application for my existing multi-vendor e-commerce SaaS platform.

Before writing code, inspect the existing repository, understand the current project structure and API integration patterns, and create an implementation plan. Then implement the solution incrementally, validating each phase before moving to the next.

Do not replace existing working code unnecessarily. Follow established project conventions wherever possible.

## 2. Existing Backend Architecture

My platform uses Laravel and has the following architecture:

- It is a multi-tenant, multi-vendor e-commerce platform.
- Each tenant has its own independent database.
- Each tenant has its own domain or subdomain.
- Examples:
  - `https://store-a.example.com`
  - `https://store-b.example.com`
  - `https://customer-domain.com`
- Each store has its own products, categories, branding, settings, customers, orders, and business data.
- A central platform manages tenants and their configuration.
- The Laravel backend is responsible for resolving tenants, connecting to the correct database, and enforcing tenant isolation.

**Important:** Do not assume that every tenant shares the same database or API hostname. Inspect the existing backend and discover how tenant resolution actually works before implementing API integration.

The Flutter application must be a single shared application that supports all tenants. We must not need to build and publish a new application whenever a new store joins the platform.

## 3. Main Objective

Build one Flutter application that supports the following flow:

1. A customer opens a store URL.
2. If the Flutter application is installed, the URL opens the application and selects the corresponding tenant.
3. If the application is not installed, the URL opens the store's website or a suitable landing page with an option to install the application.
4. After installation, the customer can open the application and select or recover the intended store.
5. The application persists the selected tenant locally.
6. On subsequent launches, the application restores the tenant automatically.
7. The customer can browse the selected store, authenticate, and place orders.
8. Switching stores must never expose another tenant's private data or authentication session.

Do not assume deferred deep linking will work automatically after installation. Implement a reliable fallback if the original store URL cannot be recovered.

## 4. Deep Linking Architecture

Implement standard, verified deep links:

- Android App Links.
- iOS Universal Links.
- Flutter deep-link routing.

Support URLs for both platform subdomains and custom domains.

Example:

`https://store-a.example.com/products/123`

Expected behavior:

- Open the app if installed and the domain is configured for app linking.
- Resolve the store identity.
- Persist the tenant context.
- Navigate to the corresponding product page.
- Fetch the product from the correct tenant API.
- Handle invalid products, deleted tenants, unavailable stores, and network errors gracefully.

Also support:

- Store homepage URLs.
- Product detail URLs.
- Category URLs.
- Cart and checkout URLs where appropriate.
- Authentication and account URLs.
- URLs containing query parameters.
- URLs with trailing slashes and URL-encoded segments.

### Deep-link security

Never trust a tenant identifier, domain, or URL parameter without server-side validation.

Use an authoritative tenant-resolution endpoint to validate the store and retrieve its canonical identity and configuration.

Never allow a deep link to specify arbitrary database credentials or arbitrary backend API hosts.

Custom domains must be validated against the platform's registered tenant domains.

If the tenant cannot be resolved, show a user-friendly error and offer a way to enter or select a store.

## 5. Tenant Resolution and API Integration

Create a dedicated tenant-resolution service.

First inspect the existing Laravel API and reuse its real endpoints and response formats.

If the necessary central endpoint does not exist, document the required backend contract and implement a compatible Flutter abstraction with mock data for development. Do not invent production endpoints and assume they exist.

The ideal resolution response should contain fields equivalent to:

- Tenant ID.
- Canonical store domain.
- Tenant display name.
- Store logo.
- Theme colors.
- Store configuration.
- API base URL or an approved API routing configuration.
- Supported features.
- Tenant status.

Adapt these fields to the existing backend instead of blindly enforcing this exact response schema.

The API layer must support:

- Central tenant resolution.
- Tenant-specific product and category requests.
- Authentication.
- Customer profiles.
- Cart and checkout.
- Orders.
- Pagination.
- Request timeouts.
- Retry policies where appropriate.
- API error handling.
- Authentication token refresh if supported by the backend.

If the backend uses a central API that dynamically resolves the tenant, follow that architecture instead of creating unnecessary tenant-specific API clients.

If each tenant genuinely has a different API hostname, configure the client only after validating the domain through the trusted central service.

## 6. Persistent Tenant Storage

Implement a dedicated `TenantStorageService`.

Use an appropriate persistent storage solution, such as `flutter_secure_storage` for sensitive credentials and `shared_preferences` or a suitable local database for non-sensitive tenant settings.

Persist only the information required to restore the store context, such as:

- Tenant ID.
- Canonical tenant domain.
- Tenant display name.
- Validated API routing configuration, if needed.
- Last selected store.
- Tenant configuration version or cache timestamp.

Avoid storing sensitive authentication credentials in ordinary preferences.

### Required startup behavior

On application startup:

1. Read the persisted tenant context.
2. Check whether a pending deep link specifies another tenant.
3. If a deep link exists, resolve and validate its tenant.
4. If the deep-link tenant differs from the stored tenant, handle the transition safely.
5. If no deep link exists, validate the stored tenant as necessary.
6. Refresh tenant configuration when required.
7. Open the correct store homepage.

Do not block the application indefinitely when the network is unavailable. Define a reasonable offline strategy that permits only safe, previously cached content when appropriate.

### Cache clearing and data recovery

Account for these situations:

- The operating system clears temporary application cache.
- The user clears application data.
- The application is uninstalled and reinstalled.
- Stored tenant configuration becomes outdated.
- The selected tenant is suspended or deleted.
- The device is offline during startup.

Temporary cache clearing should not normally erase persistent tenant settings.

If all application data is erased, recover the tenant from a new deep link or ask the user to enter the store domain or store code. Resolve it again through the trusted backend.

Never claim that ordinary local storage survives an uninstall or a complete application-data reset.

## 7. Tenant Switching and Authentication Isolation

Implement a safe tenant-switching workflow.

When switching from tenant A to tenant B:

1. Validate tenant B.
2. Stop or cancel tenant A requests where practical.
3. Clear tenant-specific in-memory state.
4. Clear or partition tenant-specific API caches.
5. Reset tenant-specific cart and checkout state.
6. Remove tenant A's authentication credentials from the active session.
7. Restore tenant B's session only if the backend explicitly supports it and the credentials are securely and correctly scoped.
8. Load tenant B's configuration and initial data.

Do not automatically reuse an authentication token across tenants unless the backend explicitly guarantees that the token is valid and appropriately scoped.

Partition cached data by tenant identity. Never display cached private customer information from another tenant.

Validate authorization on the server for every protected request. Client-side tenant switching is not a security boundary.

## 8. Application Architecture

Use a maintainable architecture suitable for a growing commercial application.

Prefer clear separation between:

- Presentation layer.
- Application/state-management layer.
- Domain models and business rules.
- Repository layer.
- API/data sources.
- Tenant resolution.
- Persistent storage.
- Authentication.
- Deep-link routing.

Inspect the current project before selecting or changing the state-management approach. If no existing convention exists, use Riverpod or another well-justified, maintainable solution.

Recommended components, adjusted to the existing project:

- `Tenant`
- `TenantConfig`
- `TenantResolutionService`
- `TenantRepository`
- `TenantStorageService`
- `TenantProvider` or equivalent state manager
- `DeepLinkService`
- `ApiClient`
- `AuthRepository`
- `StoreRouter`
- `TenantGuard`

Use dependency injection, typed models, null safety, and centralized error handling.

Do not put tenant-resolution logic directly inside UI widgets.

## 9. Dynamic Store Branding

The application must dynamically load each store's branding.

Support:

- Store logo.
- Primary and secondary colors.
- Store name.
- Supported languages.
- Currency and formatting settings.
- Enabled store features.
- Configurable navigation where appropriate.

Provide safe defaults if branding data is incomplete.

Do not require a new application build for normal branding changes or newly onboarded tenants.

Do not execute arbitrary remote code or download executable application logic as part of tenant configuration.

## 10. Store Pages and E-commerce Flow

Implement or integrate the following customer-facing features according to the existing project scope and API capabilities:

- Store homepage.
- Product listing.
- Product details.
- Categories.
- Search and filtering.
- Cart.
- Checkout.
- Customer registration and login.
- Customer profile.
- Order history and order details.
- Loading, empty, offline, and error states.

All tenant-specific requests must use the currently validated tenant context.

If some APIs are unavailable, create clearly isolated interfaces and mock implementations for development. Document which integrations require backend work.

Do not fabricate successful checkout, payment, or order-creation responses.

## 11. App Installation and First-Open Flow

Support the following scenarios.

### Scenario A: App already installed

The customer opens a store URL.

Expected result:

- The operating system opens the app.
- The app resolves the store.
- The tenant is persisted.
- The requested page opens.

### Scenario B: App not installed

The customer opens a store URL.

Expected result:

- The store website or landing page opens.
- The page provides an install link.
- The store identity is preserved in a normal web-accessible URL or another reliable handoff mechanism.

After installation, if the original URL is not automatically delivered to the application, show a tenant-entry screen or provide another reliable recovery mechanism.

Implement the standard deep-link flow first. Treat true deferred deep linking as an optional integration requiring a compatible service or additional infrastructure, not as a guaranteed native capability.

### Scenario C: App opened normally

If a tenant is saved, restore and validate it.

If no tenant is saved, show a store selection, store URL, or store code entry screen.

### Scenario D: App opened with another store URL

Resolve the incoming tenant and transition safely. If the user is authenticated to another tenant, handle the session transition explicitly.

## 12. Android and iOS Configuration

Configure and document the requirements for:

### Android

- Intent filters for HTTPS App Links.
- `android:autoVerify`.
- `assetlinks.json`.
- The correct application package name.
- The release signing certificate SHA-256 fingerprint.
- Domain association for platform and custom domains.

### iOS

- Associated Domains entitlement.
- `apple-app-site-association`.
- Correct Team ID and Bundle ID.
- Universal Link configuration.
- Required domain association for custom domains.

Do not assume one domain's verification automatically covers every tenant domain.

Design the linking architecture to support many tenants without requiring a separate application build per tenant.

Investigate the scalability and ownership implications of custom domains. If registering every custom domain as an associated domain is impractical, propose and document a verified platform-domain or web-fallback strategy.

## 13. Testing Requirements

Write meaningful automated tests.

### Unit tests

- Tenant model serialization.
- Tenant resolution.
- URL parsing and normalization.
- Tenant storage.
- Tenant state restoration.
- Configuration refresh.
- Tenant switching.
- Authentication isolation.
- Error handling.

### Widget tests

- First launch without a tenant.
- Loading tenant configuration.
- Invalid tenant screen.
- Store homepage.
- Store switching.
- Network failure states.

### Integration tests

- Open a store deep link.
- Open a product deep link.
- Restore the last selected tenant.
- Switch between two tenants.
- Recover after cleared application data.
- Reject an invalid or unregistered domain.
- Verify that tenant A's cached information is not displayed for tenant B.

Use mock services and fixtures for deterministic tests. Do not require production credentials or live customer data to run the test suite.

## 14. Security Requirements

- Validate tenant identities against the trusted backend.
- Never expose database credentials in Flutter.
- Never trust locally stored tenant information without appropriate validation.
- Never allow untrusted URLs to control arbitrary API hosts.
- Keep authentication tokens in secure storage.
- Scope sessions and caches to the appropriate tenant.
- Prevent cross-tenant data leakage.
- Avoid logging tokens, passwords, or sensitive customer information.
- Validate payment and checkout operations on the backend.
- Handle expired sessions and suspended tenants safely.

## 15. Development Workflow

Follow these phases:

**Phase 1 — Repository and backend discovery**
- Inspect the Flutter repository.
- Identify existing dependencies, routing, state management, API clients, and authentication.
- Inspect available Laravel API documentation or backend source if accessible.
- Determine the actual tenant-resolution mechanism.
- Identify missing backend contracts and domain-association requirements.
- Report findings and implementation plan.

**Phase 2 — Core tenant infrastructure**
- Implement tenant models.
- Implement tenant resolution.
- Implement persistent storage.
- Implement deep-link parsing and routing.
- Implement safe tenant switching.

**Phase 3 — Store integration**
- Integrate tenant-specific APIs.
- Implement dynamic branding.
- Connect the existing e-commerce screens.
- Implement session and cache isolation.

**Phase 4 — Platform integration**
- Configure Android App Links.
- Configure iOS Universal Links.
- Document domain verification and signing requirements.
- Implement the website/installation fallback.

**Phase 5 — Quality assurance**
- Run formatting and static analysis.
- Run unit and widget tests.
- Run integration tests where the environment permits.
- Fix regressions.
- Review tenant isolation and security.

After each phase, report the files changed, tests run, issues discovered, and remaining work.

## 16. Acceptance Criteria

The implementation is complete when:

- One Flutter application supports multiple tenants.
- A validated store URL opens the correct tenant.
- Tenant context persists across normal application restarts.
- Clearing temporary cache does not unnecessarily remove the saved tenant.
- Clearing all application data has a documented recovery flow.
- The application does not depend on unreliable deferred deep-link assumptions.
- Store branding loads dynamically.
- Switching tenants clears or partitions tenant-specific state correctly.
- Authentication sessions do not leak across tenants.
- Invalid and suspended tenants are handled safely.
- No database credentials are shipped in the app.
- Automated tests cover the main tenant lifecycle.
- Android and iOS deep-link setup is documented.
- New tenants do not require new Flutter builds under the shared-app architecture.

## 17. Final Instructions

Start by inspecting the repository and identifying the real API contracts.

Do not stop after generating an architecture document. Implement the working solution.

Do not rewrite the entire application if targeted changes are sufficient.

Do not invent backend endpoints, pretend tests passed, or claim platform verification is complete without evidence.

When finished, provide:

1. Architecture summary.
2. Implemented features.
3. Files created or modified.
4. Required Laravel backend changes.
5. Android and iOS configuration instructions.
6. Required DNS and domain-verification setup.
7. Commands used to run and test the app.
8. Outstanding issues and production deployment checklist.

Prioritize correctness, tenant isolation, maintainability, and production readiness over unnecessary abstractions.
