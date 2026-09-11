# Prompt 10 — Settings (all tenant settings pages)

Requires prompts 01–03 and 07 (the payment modal). Follow the **Standard Conversion Recipe** (prompt 00).
Controllers: `app/Http/Controllers/Tenant/Panel/Settings/`. Views: `resources/views/tenant/pages/settings/`. JS: `resources/js/tenant/pages/settings/`.
Keep each route's permission exactly as in `routes/tenant_panel.php` today (`settings.tracking.manage`, `store.subscribers.manage`, `settings.regional.manage`, `settings.domains.manage`, `settings.translations.manage`, `settings.admins.manage`, `settings.roles.manage`, `settings.payment-gateways.manage`, `settings.mail.manage`, `settings.account.manage`, `sales.returns.manage`).

## 0. New shared component: `x-tenant::schema-fields`
Several pages extend `ContentPage` and describe their form as `fieldGroups` arrays: `['label','model','type','options','wrapperClass','placeholder','help']`. This applies to `GeneralSettingsPage`, `MailConfigurationsPage`, `ReturnPolicyPage` and (prompt 08) `CreateTicket` / `CreateRequest`. `content-page.blade.php` renders them.

To port them 1:1 without hand-writing every field, add `resources/views/tenant/components/schema-fields.blade.php`:
- Props: `groups`, `values` (array), `name-map` (`camelModel => html_name`, default `Str::snake`).
- It renders each group as `x-tenant::card` with a title/description and a grid, and each field through the matching component:

| `type` | component |
|---|---|
| text / email / number / password / url | `input` |
| textarea | `textarea` |
| select | `select`, or `select2` when `searchable` / `multiple` |
| checkbox / toggle | `switch` |
| editor | `editor` |
| date | `date` |
| color | `color` |
| file | `file` |

- `wrapperClass` (e.g. `span-2`) is preserved.
- The controller builds `groups` with the **same** arrays the Livewire `pageData()` returns, minus the Livewire model binding. The field `name` = the snake_case of `model`.

---

## 1. Tracking — `tenant.settings.tracking` (`TrackingSettingsPage`)
- Form: `fb_pixel_id`, `tiktok_pixel_id`, `snapchat_pixel_id`, `ga_measurement_id` (rules from `save()`).
- `PUT settings/tracking` (+validate). Toast "Tracking settings saved successfully.".

## 2. Subscribers — `tenant.settings.subscribers` (`SubscribersPage`)
- Stats `subscriberStats()`. Columns Email, Subscribed At, Actions. Data from the builder behind `paginateSubscribers()`.
- Delete → `DELETE settings/subscribers/{subscriber}` → `deleteSubscriber`, with the same confirm and toast "Subscriber deleted successfully.".
- Export → `GET settings/subscribers/export` using `exportSubscribers()` and the same headers.

## 3. Currencies — `tenant.settings.currencies` (`CurrenciesPage`)
- Filters `search`, `status`. Columns Currency, Rate, Status, Actions.
- Status is a `switch` → `PATCH settings/currencies/{currency}/active` → "Currency state updated successfully.".
- Make default → `POST settings/currencies/{currency}/default` → `markDefaultCurrency`, toast "Default currency updated successfully.". It reloads the table (the default badge moves).
- Port the "cannot deactivate the default currency" type guards if present.

## 4. Domains — `tenant.settings.domains` (`DomainsList` → `setting/domains-list.blade.php`, 403 lines, inline `<style>`)
- `index()` shows domain requests (`DomainRequest::query()` scoped to the tenant) and connected domains (`Domain::where('tenant_id')`), plus the DNS instructions. Both lists render as client-mode datatables (short lists). Status uses `status-badge`.
- Add → `POST settings/domains`, porting the exact closure rules in `addDomain()`: normalisation, "already connected" / "already requested" checks, and the `DomainRequest::create` payload. Toast "Domain request submitted. Our team will review it.".
- Edit → `GET settings/domains/{domainRequest}` (JSON) + `PUT settings/domains/{domainRequest}`, porting `updateDomain()` including the connected → pending transition that detaches `Domain`. Toast "Domain updated. Please re-verify the DNS records.".
- Remove → `DELETE settings/domains/{domainRequest}` (confirm), toast "Domain removed.".
- Check DNS → `POST settings/domains/{domainRequest}/check-dns` → `DnsRecordService::checkDomain()`. The toast type comes from the result, and the result panel (the current `dnsCheckResult` markup) renders from `data`.
- DNS record values get `x-tenant::copy`.
- Validate routes exist for add and edit. **Keep the normalisation in the FormRequest's `prepareForValidation()`**, so validate and submit agree.

## 5. Languages — `tenant.settings.languages` (`LanguagesPage`) and `tenant.settings.languages-manage` (`LanguagesManagePage`, 520-line view with 2 tables + payment)
- **Languages:**
  - columns Language, Direction, Type, Status, Actions
  - status switch → `PATCH settings/languages/{language}/active`, "Language state updated successfully."
  - default → `POST settings/languages/{language}/default`, "Default language updated successfully."
- **Languages manage:**
  - The two tables (active/tenant languages, and languages available for purchase) become two datatables:
    - `GET settings/languages-manage/data`
    - `GET settings/languages-manage/available/data` (`DataTables::collection(availableForPurchase())`)
  - `toggleActive`: keep the plan-limit check (`canPerform(FEATURE_LANGUAGES)` → `PanelActionException(errorMessage(...))`).
  - `makeDefault`: as above.
  - Buy opens `x-tenant::payment.gateway-modal` with `language_id` → `POST settings/languages-manage/purchase`. Port `initiatePayment()` exactly ("You have already purchased this language.", the pending session, stash tokens, redirect `tenant.language-purchase.charge`).

## 6. AI translation — `tenant.settings.ai-translation` (`AiTranslationPage`, 459-line view, 4 scripts, `wire:poll.3s` while running)
- `index()` shows the language cards (`availableLanguages()`, `isFree()`, `canPurchase()`, `history()`).
- The history table becomes a datatable (`DataTables::collection(history())`) at `GET settings/ai-translation/history/data`.
- **Run free** → `POST settings/ai-translation/{language}/run`. Guards and messages:
  - "This language requires payment or is not enabled on your plan." → 422
  - AI-calls plan limit → `errorMessage(FEATURE_AI_CALLS)`
  - `incrementCounter`
  - dispatch, as today
  - toast "AI translation started. This may take a few minutes."
- **Paid** → the payment modal → `POST settings/ai-translation/{language}/purchase`:
  - "This language is free — no payment required." → 422
  - the plan-limit check
  - pending session, stash tokens, redirect `tenant.ai-translation-purchase.charge` with the same params
  - Gateways: the same source as the class.
- **Polling:** replace `wire:poll.3s` (active while `$polling`) with `GET settings/ai-translation/status` returning the per-language progress. `pages/settings/ai-translation.js` polls every 3 s **only while any job is running and the tab is visible**, patches the progress bars in place, then stops.

## 7. Translations — `tenant.settings.translations` (`TranslationsPage`)
- **Filters:**
  - `language_id` (select2, required — replaces `updatedSelectedLanguageId`)
  - `search`
  - `only_missing` (switch)
- **Table:** a server-side datatable over `TenantTranslationService::keysForLocale(...)` (`DataTables::collection`, filtered and paged server-side). Replace the manual `setPage`/`perPage`.
  - Columns: select checkbox, Key, Default text, Translation (an inline `x-tenant::textarea` per row + a Save button), Actions (AI translate).
- **Endpoints:**
  - `saveKey` → `PUT settings/translations/{language}/keys` (`key`, `value`) → `saveOverride`. Toast "Translation saved successfully." or the exception message (422).
  - `translateKeyWithAi` → `POST settings/translations/{language}/keys/ai` (`key`):
    - `aiTranslationEnabled()` guard → `aiTranslationErrorMessage()`
    - AI-calls limit
    - success toast "Key translated with AI successfully."
    - returns the new value; the JS fills the row
  - `translateSelectedWithAi` → `POST settings/translations/{language}/keys/ai-bulk` (`keys[]`): "Select at least one key to translate." when empty; "{count} key(s) translated with AI successfully.". Use the datatable `selectable` bulk bar.
  - `translateStore` → `POST settings/translations/{language}/store-ai`, toast "Store translation queued. This may take a while.". Polling as in §6 (`GET settings/translations/{language}/status`) replaces `wire:poll.3s`.

## 8. Admins — `tenant.settings.admins` (`AdminsList`)
- Stats `adminStats()`. Filters `search`, `status`. Columns Admin, Email, Role, Status, Last Login, Actions.
- Modal create/edit:
  - `name`, `email` (unique among tenant admins, ignoring the current id — copy the rule)
  - `password` (required on create, nullable on edit — copy the rule), with `toggle`
  - `role_id` (select from `roleOptions()`)
  - `status`
  - `saveAdmin`
  - "Tenant admin created successfully." / "Tenant admin updated successfully."
- Activate / deactivate → `POST settings/admins/{admin}/activate|deactivate`, "Tenant admin enabled successfully." / "Tenant admin disabled successfully.".
- Delete → `DELETE settings/admins/{admin}` with a confirm. **The self-delete guard** returns `PanelActionException('You cannot delete the currently signed-in tenant admin.', 422, toastType: 'warning')`. Success "Tenant admin deleted successfully.".
- The Delete action is hidden for the current user's row (the same `@if` as today).

## 9. Roles & permissions — `tenant.settings.roles-permissions` (`RolesPermissionsList`)
- Stats `adminRoleStats()`. Filter `search`. Columns Role, Permissions, Admins Assigned, Updated At, Actions.
- Modal:
  - `name`
  - `permissions[]` — `x-tenant::checkbox-group` **grouped by module** from `availableAdminPermissions()`, with select-all per group; keep the grouping the current view uses
  - `saveAdminRole`
  - "Tenant role created successfully." / "Tenant role updated successfully."
- Delete → `deleteAdminRole` with a confirm (port its guards, e.g. a role in use), "Tenant role deleted successfully.".

## 10. Payment gateways — `tenant.settings.payment-gateways` (`PaymentGatewaysPage`) + `tenant.settings.payment-readiness` (`PaymentReadinessPage`)
- **Gateways list:**
  - Recommendations card (`PaymentGatewayRecommendationService`, the current markup).
  - Columns Gateway, Mode, Status, Connection, Webhook, Monitoring, Actions. Use `DataTables::collection` or eloquent, the same source as `pageData()`.
  - Set primary → `POST settings/payment-gateways/{gateway}/primary`, toast "{name} is now the primary gateway.".
  - Check connection → `POST settings/payment-gateways/{gateway}/check` (`GatewayConnectionChecker`). The toast type comes from `$result` (success → 200 with message; failure → 422 message).
  - **Edit modal:**
    - `GET settings/payment-gateways/{gateway}` returns `is_active`, `use_own`, `mode`, `sandbox_mode`, `webhook_url` and `required_fields[] {key,label,type,value(masked for secrets)}`, exactly what `editGateway()` loads.
    - The modal renders the dynamic credential fields from that JSON via a small `<template>` in `pages/settings/payment-gateways.js` (keys: `required_fields[i][key|value]`), with `x-tenant::copy` on the webhook URL.
    - `save()` → `PUT settings/payment-gateways/{gateway}`. Port the dynamic rules loop (`required_fields.*.value` required when `use_own`) and the **connection test before save**: on failure, add error `required_fields` "Connection failed: {message}" as a 422 field error.
    - Success: "Connected and saved successfully." when `use_own`, else "Gateway configuration updated successfully.".
    - Then emit `tenant:setup-progress:refresh`.
  - `fromOnboarding` (the query flag set in `mount()`): keep the behaviour — after saving, if `?from=onboarding`, return `redirect` to the onboarding setup tab.
- **Payment readiness:** port `setting/payment-readiness.blade.php` (a read-only checklist from `PaymentReadinessService`). No JS unless the view has toggles.

## 11. Email templates — `tenant.settings.email-templates` (`EmailTemplatesPage`) + `.email-templates.edit` (`AddEditEmailTemplate`)
- **List:** stats `emailTemplateStats()`, filters `search`, `status`, columns Template, Event, Subject, Body, Status, Updated At, Actions (Edit).
- **Edit:**
  - `name`, `action` (read-only event)
  - `is_active` switch
  - `locale-tabs` with `translations.{code}.subject` + `translations.{code}.body` (`x-tenant::editor`) — replaces `setActiveLocaleTab`
  - the variable reference list (click to copy → `x-tenant::copy`)
  - `save` → `PUT settings/email-templates/{emailTemplate}` → `saveEmailTemplate`, redirect back to edit with the current message
  - `resyncFromCentral` → `POST settings/email-templates/{emailTemplate}/resync` (confirm) → `TenantCatalogSyncService::syncForTenant`, then `reload-page`, with a toast per the current code (read it)

## 12. Mail — `tenant.settings.mail` (`MailConfigurationsPage` + `setting/partials/test-email-modal.blade.php`)
- `schema-fields` with the current field groups (mailer, host, port, username, password with `toggle`, encryption, from address/name).
- `PUT settings/mail` → `saveMailSettings`, toast "Mail configuration overrides saved successfully. Blank fields will continue using the central mail configuration.".
- Test email modal → `POST settings/mail/test` (`email` required email) → `sendTest`. The toast type follows `$result['success']` (failure → 422 with the message).

## 13. Account — `tenant.settings.account` (existing `AccountSettingsController` + `tenant/setting/account-settings.blade.php`)
- Move to `Settings\AccountSettingsController`. Rules go into `UpdateAccountRequest` (from the current `update()`).
- `PUT settings/account` (name `tenant.settings.account.update`, kept) returns JSON `success(<current flash message>)`, plus `POST settings/account/validate`.
- Rewrite the view with components (`phone`, `password toggle`). The inline `<style>` moves to CSS.

## 14. General — `tenant.settings.general` (`GeneralSettingsPage` → `setting/general-settings.blade.php`, inline `<style>`)
- Profit percentage form: `profit_percentage` required numeric 0–1000 → `PUT settings/general`. Port `save()` including the price recalculation dispatch. Toast "General settings updated. Prices are being recalculated for all products and variants.".
- Country change request modal:
  - `requested_country_ids[]` (select2 multiple)
  - `POST settings/general/country-request`
  - pending guard → `PanelActionException('A target countries change request is already pending review.', 409, toastType: 'warning')`
  - success "Request sent to admin for review."
  - `AdminNotificationService` as today
- Category change request modal: `requested_category_ids[]` (select2 multiple tree) → `POST settings/general/category-request`, with the analogous guard and messages.

## 15. Compliance center — `tenant.settings.compliance` (existing `ComplianceCenterController` + `tenant/setting/compliance-center.blade.php`, inline script and style)
- Move to `Settings\ComplianceCenterController`. Rules go into `UpdateComplianceRequest`.
- `POST settings/compliance` (name `tenant.settings.compliance.update`, kept) returns JSON `success(...)`, plus `/validate`.
- The country → city dependency uses `x-tenant::select2 data-depends-on` with `tenant.settings.compliance.cities-by-country` (return select2 shape via `?format=select2`; keep the old shape otherwise).
- Uploads use `x-tenant::file` / `image-upload`. The inline script and style move to `pages/settings/compliance.js` / CSS.

## 16. Return policy — `tenant.settings.return-policy` (`ReturnPolicyPage`)
- `schema-fields` with the current groups:
  - Return Window (days)
  - Return Fee
  - Non-returnable Product IDs → **upgrade to `x-tenant::select2 multiple ajax-url`** product search while still submitting the same comma-separated/array value the save expects. Normalise in `prepareForValidation`.
  - `videoRequiredReasons` → checkbox-group with the current options
  - Accepted Conditions (textarea)
- `PUT settings/return-policy`. Port `save()` including its try/catch: failure → 500-style 422 "Something went wrong while saving the return policy. Please try again.". Success "Return policy saved successfully.".

## 17. Routes to add (inside `settings` prefix, each with its page's permission)
```
tracking:            PUT tracking (+validate)
subscribers:         GET subscribers/data · GET subscribers/export · DELETE subscribers/{subscriber}
currencies:          GET currencies/data · PATCH currencies/{currency}/active · POST currencies/{currency}/default
domains:             POST domains (+validate) · GET domains/{domainRequest} · PUT domains/{domainRequest} (+validate) · DELETE domains/{domainRequest} · POST domains/{domainRequest}/check-dns
languages:           GET languages/data · PATCH languages/{language}/active · POST languages/{language}/default
languages-manage:    GET languages-manage/data · GET languages-manage/available/data · PATCH languages-manage/{language}/active · POST languages-manage/{language}/default · POST languages-manage/purchase (+validate)
ai-translation:      GET ai-translation/history/data · GET ai-translation/status · POST ai-translation/{language}/run · POST ai-translation/{language}/purchase (+validate)
translations:        GET translations/data · PUT translations/{language}/keys · POST translations/{language}/keys/ai · POST translations/{language}/keys/ai-bulk · POST translations/{language}/store-ai · GET translations/{language}/status
admins:              GET admins/data · GET admins/{admin} · POST admins (+validate) · PUT admins/{admin} (+validate) · POST admins/{admin}/activate · POST admins/{admin}/deactivate · DELETE admins/{admin}
roles-permissions:   GET roles-permissions/data · GET roles-permissions/{role} · POST roles-permissions (+validate) · PUT roles-permissions/{role} (+validate) · DELETE roles-permissions/{role}
payment-gateways:    GET payment-gateways/data · GET payment-gateways/{gateway} · PUT payment-gateways/{gateway} (+validate) · POST payment-gateways/{gateway}/primary · POST payment-gateways/{gateway}/check
email-templates:     GET email-templates/data · PUT email-templates/{emailTemplate} (+validate) · POST email-templates/{emailTemplate}/resync
mail:                PUT mail (+validate) · POST mail/test (+validate)
account:             POST account/validate   (PUT account kept)
general:             PUT general (+validate) · POST general/country-request (+validate) · POST general/category-request (+validate)
compliance:          POST compliance/validate   (POST compliance kept)
return-policy:       PUT return-policy (+validate)
```
Names follow `tenant.settings.<page>.<action>`. Declare static segments (`data`, `export`, `status`, `available`, `purchase`) before wildcards.

## 18. Vite entries
One entry per page:
```
settings/tracking.js, subscribers.js, currencies.js, domains.js, languages.js, languages-manage.js, ai-translation.js,
translations.js, admins.js, roles-permissions.js, payment-gateways.js, payment-readiness.js, email-templates-index.js,
email-template-form.js, mail.js, account.js, general.js, compliance.js, return-policy.js
```
Each is prefixed `resources/js/tenant/pages/settings/` and listed explicitly in `vite.config.js`. Omit entries for pages whose markup needs no JS beyond the core, e.g. payment-readiness if static; then do **not** push an `@vite` for that page.

## 19. Verification
1. Every settings page loads on the new layout with zero Livewire.
2. Each form: blur validation, submit toast, 422 inline errors. Warning toasts where Livewire used `warning` (self-delete admin, pending change requests).
3. Payment gateways: the dynamic credential fields, the connection-test failure shows under the fields, primary switch, the onboarding return flow.
4. AI translation / translations polling starts only while jobs run, stops afterwards, and pauses in background tabs.
5. Languages-manage purchase reaches `tenant.language-purchase.charge` with the same session keys.
6. Domains: duplicate domain → inline error, DNS check result panel, connected → edit → pending transition.
