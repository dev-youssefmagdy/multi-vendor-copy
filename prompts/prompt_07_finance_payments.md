# Prompt 07 — Finance + the Shared Inline-Payment Modal

Requires prompts 01–03. Follow the **Standard Conversion Recipe** (prompt 00).
Controllers: `app/Http/Controllers/Tenant/Panel/Finance/`. Views: `resources/views/tenant/pages/finance/`. JS: `resources/js/tenant/pages/finance/`.
Keep middleware exactly:
- wallet and payouts: `finance.wallet.view`
- billing: `finance.billing.view`
- vendor purchases / settle / settlement payments: `finance.vendor-purchases.view`
- buy languages: `settings.languages.purchase`

**Do not touch** the gateway payment controllers (`SubscriptionPaymentController`, `LanguagePaymentController`, `AiTranslationPaymentController`, `ManufacturingPaymentController`, `BrandRequestPaymentController`, `VendorSettlementPaymentController`) or their `charge|success|cancel` routes. The new endpoints only prepare the session and return a `redirect` to the existing `…charge` route, exactly as the Livewire `$this->redirect(...)` did.

---

## 1. Shared inline-payment building blocks (used here and by prompts 08 and 10)

Today **7 Livewire classes** copy the same flow:
- `WalletPage`, `BuyLanguagePage`, `VendorSettleOrderPage`, `LanguagesManagePage`, `AiTranslationPage`, `ManufacturingRequestDetail` and `BrandRequestDetail`
- The flow is: a gateway list → an optional inline card form for `stripe` / `authorize_net` / `2checkout` → client tokenisation → `stashInlineTokens()` writes the session keys → redirect to the gateway `charge` route.
- Each of the 7 views carries ~150 lines of identical inline JS (`spInitPaymentForm`, `spSetupCardFormatting`, Stripe `createToken`, Accept.js `dispatchData`, 2pay `TwoPayClient`) and `<script src>` tags for the three SDKs.

### 1.1 PHP
- `app/Support/Tenant/Payments/InlineGatewayPresenter.php` — `present(Collection $gateways, ?string $selected = null): array`.
  - Port the block from `WalletPage::pageData()` (lines ~121–175) that builds `inlineCardCodes`, `inlineGatewayMap` (`code`, `name`, `creds` → Stripe publishable `key`; Authorize.Net `api_login_id` + `client_key`; 2Checkout `seller_id`), `hasStripe`, `hasAuthorizeNet`, `has2Checkout` and `authNetSandbox`.
  - It returns `['gateways' => [...{code,name,logo,is_inline,creds(public only)}], 'sdk' => ['stripe'=>bool,'authorize_net'=>'live'|'sandbox'|null,'2checkout'=>bool]]`.
  - **Only public/publishable credentials may leave the server.** Assert that no secret keys are ever included (whitelist per gateway).
- `app/Http/Controllers/Tenant/Panel/Concerns/StashesInlinePaymentTokens.php` — `protected function stashInlineTokens(Request $request, string $gateway): void`. It writes **exactly the same session keys** as the Livewire `stashInlineTokens()`:
  - `pgtoken_stripe_stripeToken` ← `stripe_token`
  - `pgtoken_authorize_net_opaqueDataDescriptor` ← `authnet_desc`
  - `pgtoken_authorize_net_opaqueDataValue` ← `authnet_value`
  - `pgtoken_2checkout_2co_token` ← `twoco_token`
  - Same conditions (non-empty values, per gateway).
- `app/Http/Requests/Tenant/Panel/Finance/Concerns/InlinePaymentRules.php` — a trait for the FormRequests: `gateway` required string in the page's gateway codes, plus the four token fields `nullable|string|max:2048`.

### 1.2 Blade: `x-tenant::payment.gateway-modal`
- Props: `id`, `title`, `action` (initiate URL), `presented` (the presenter output), `submit-label` (e.g. "Renew Plan" / "Upgrade Plan" / "Pay now"), `selected`; slots `summary` (package/language/order summary) and `fields` (hidden context inputs such as `package_id`, `type`, `language_id`).
- Markup: port the gateway selection + inline card form + summary from `livewire/tenant/finance/wallet-page.blade.php` (the `x-modal` block and the `#sp-inline-card-form` markup, line ~260–345), with all `wire:*` removed.
  - Gateways are `x-tenant::radio-group variant="cards" name="gateway"`.
  - Card containers exist for each inline gateway, all hidden until selected: `#sp-stripe-card-element` + `#sp-stripe-card-errors`, and `#sp-card-number` / `#sp-card-expiry` / `#sp-card-cvc` for Authorize.Net/2CO. Keep the ids **per modal instance**, suffixed with `-{id}` so two modals can coexist.
  - Hidden `stripe_token`, `authnet_desc`, `authnet_value`, `twoco_token`.
  - The public creds go in `data-*` on the modal root.
- Wrapped in `x-tenant::form action=… success="redirect"`.

### 1.3 JS
- `resources/js/tenant/modules/payment-sdk-loader.js` — `loadStripe()`, `loadAcceptJs(sandbox)` and `load2Pay()`. Each injects `<script src>` **once** (memoised promise, `crossorigin` where supported) for `https://js.stripe.com/v3/`, `https://js.authorize.net/v1/Accept.js` / `https://jstest.authorize.net/v1/Accept.js` and `https://2pay-js.2checkout.com/v1/2pay.js`, and rejects after 15 s.
  - This is the **only** documented CDN exception (PCI). Add a header comment explaining why these are not self-hosted.
- `resources/js/tenant/modules/payment-gateway.js` — `initPaymentModal(modalEl)`:
  - On gateway change: reset the tokens, lazily load the SDK and mount. The Stripe card element is themed from CSS vars (the same style object as today, `hidePostalCode: true`). Authorize.Net/2CO get card formatting (port `spSetupCardFormatting`).
  - Hook the form's submit through `TenantForm` (`beforeSubmit` hook):
    - **Stripe** → `createToken`; on error, show it in `#sp-stripe-card-errors` and abort.
    - **Authorize.Net** → `Accept.dispatchData(secureData)`, with the expiry parsing and response handling ported verbatim, filling `authnet_desc` / `authnet_value`.
    - **2Checkout** → port the `TwoPayClient` token flow verbatim, filling `twoco_token`.
    - Non-inline gateways submit directly.
  - Replace every `alert(...)` with `toast.error(...)`, keeping the same messages ("Stripe.js is still loading. Please try again in a moment.", "Accept.js is still loading. Please try again.", …).
  - Register it as a component: `[data-tenant-payment-modal]`.

---

## 2. Wallet — `tenant.finance.wallet` (`Finance\WalletPage` → `finance/wallet-page.blade.php`)
- `index()`:
  - `walletOverview()` stats and the current package/subscription panel.
  - Renew/Upgrade buttons open the modal. `openRenewModal` / `openUpgradeModal` logic moves to data attributes: renew preselects the current package; upgrade lists the published packages ≠ current, exactly as the current `pageData()` `$packages` query does.
  - `InlineGatewayPresenter::present(app(PaymentManager::class)->vendorPaymentGateways())`.
- **Two tables → two datatables:**
  - **Subscriptions**: `GET finance/wallet/subscriptions/data` from the builder behind `paginateSubscriptions()` (expose `querySubscriptions()`).
  - **Transactions**: `GET finance/wallet/transactions/data` from `queryTransactions()`.
  - Columns are exactly the current table headers and cells in `wallet-page.blade.php`.
- `initiatePayment()` → `POST finance/wallet/subscription` → `WalletController@subscribe(SubscribeRequest)`:
  - rules `package_id` required integer, `type` in `renew,upgrade`, plus `InlinePaymentRules`
  - `Package::where('id')->where('status', PackageStatus::Published)->firstOrFail()`
  - `stashInlineTokens`
  - `session(['tenant_subscription_pending_payment' => [...]])` (the same keys)
  - `success('Redirecting to payment…', redirect: route('tenant.subscription-payment.charge', ['gateway'=>…,'packageId'=>…,'type'=>…]))`
  - Plus `POST finance/wallet/subscription/validate`.

## 3. Billing — `tenant.finance.billing` (`Finance\BillingPage`) + detail `tenant.finance.billing.detail` (`Finance\BillingDetailPage`)
- **List:**
  - stats `billingStats()` + the plan `usage()` panel (`PlanLimitService`)
  - filters `search`, `paid` (paid/unpaid/all), `gateway` (`paymentMethodOptions()`)
  - columns Order, Customer, Totals, Payment, Gateway, Placed At, Payment Details, Actions
  - data from `queryOrders(['search','paid','gateway'])`: the builder already supports `paid` and `gateway`
- **Detail:** `show(int $orderId)` → port `billing-detail-page.blade.php` (23 lines, it likely includes the order-details partial). Reuse the Sales order-details partial from prompt 06 instead of duplicating it.

## 4. Vendor purchases — `tenant.finance.vendor-purchases` (`Finance\VendorPurchasePage`)
- `index()`:
  - `vendorPurchaseStats()` cards
  - filters `search`, `settled`
  - columns Order, Customer, Paid Via, Product Cost, Shipping, Gateway Fee, Total Due, Settlement, Actions (Settle → `tenant.finance.vendor-purchase-settle`)
  - export `GET finance/vendor-purchases/export` (the same headers/rows)
- **Flash toasts:** `mount()` pulls `vendor_settlement_success` / `vendor_settlement_error`. Instead, extend `tenant/layouts/partials/flash.blade.php` (prompt 03) with a **generic mapping** of every payment-controller flash key to a toast type, so all payment return pages toast consistently:
  - success: `payment_success`, `purchase_success`, `subscription_success`, `vendor_settlement_success`, `mf_payment_success`, `br_payment_success`
  - error: `purchase_error`, `vendor_settlement_error`, `mf_payment_error`, `br_payment_error`
  - warning: `payment_cancelled`, `subscription_cancelled`
  - Render each as a `#flash-*` element that `showFlash()` consumes, and `session()->forget()` the pulled ones in the partial.

## 5. Vendor settle order — `tenant.finance.vendor-purchase-settle` (`Finance\VendorSettleOrderPage` → `finance/vendor-settle-order-page.blade.php`)
- `show(int $orderId)`: port `mount()` (order + guards) and the `$breakdown` computation (via `VendorPurchaseService`, as today). The breakdown table is `x-tenant::table`.
- Gateways: `InlineGatewayPresenter::present($repo->centralGatewaysForPayment())` (central credentials via `PaymentManager::centralGateway()`, as the class docblock says).
- `initiateGatewayPayment()` → `POST finance/vendor-purchases/{orderId}/settle` → `settle(SettleOrderRequest)`: port the validation, stash the tokens, then `redirect route('tenant.vendor-settlement.charge', …)` with the same params.
- The inline `<style>` moves to `resources/css/tenant/pages/finance.css`. The 4 script blocks are replaced by the shared payment modal/module. The page can embed the gateway selector inline (not as a modal): use the same component with `inline` (add an `inline` prop to render without the modal chrome).

## 6. Settlement payments — `tenant.finance.settlement-payments` (`Finance\SettlementPaymentsPage`)
- The query lives in the class (`VendorSettlement::query()->where('tenant_id', …)`, a central model). Move it to `TenantPanelRepository::querySettlementPayments(array $filters)` + `settlementPaymentStats()`, keeping the exact stats (total paid count, total paid sum, this month, statuses).
- Filters `search`, `status`. Columns Invoice, Order, Gateway, Transaction, Amount, Settled At.

## 7. Payouts received — `tenant.finance.payouts` (`Finance\PayoutsReceivedPage`)
- Same pattern with `TenantPayout` (central): `queryPayouts()` + `payoutStats()` moved from the class unchanged.
- Filters `search`, `status`. Columns Invoice, Method, Amount, Status, Paid At.

## 8. Buy languages — `tenant.finance.buy-languages` (`Finance\BuyLanguagePage` → `finance/buy-language-page.blade.php`)
- `index()`: `LanguagePurchaseService::availableForPurchase()` cards/table. The current `<table>` becomes `x-tenant::datatable` (`DataTables::collection` of the available languages, with the same columns and a Buy action that opens the payment modal with `language_id`).
- Gateways: `InlineGatewayPresenter::present(PaymentManager::vendorPaymentGateways())` (the same source as the class).
- `initiatePayment()` → `POST finance/buy-languages` → `purchase(BuyLanguageRequest)`:
  - validation as today
  - `CentralLanguage` lookup
  - **"You have already purchased this language."** → 422 `PanelActionException`
  - pending-purchase session keys unchanged
  - stash tokens
  - redirect `tenant.language-purchase.charge`
  - plus a `/validate` route.

## 9. Routes to add
```
finance/wallet/subscriptions/data · finance/wallet/transactions/data · POST finance/wallet/subscription (+/validate)
finance/billing/data
finance/vendor-purchases/data · finance/vendor-purchases/export · POST finance/vendor-purchases/{orderId}/settle (+/validate)
finance/settlement-payments/data · finance/payouts-received/data
finance/buy-languages/data · POST finance/buy-languages (+/validate)
```
- Names: `tenant.finance.wallet.subscriptions.data`, `tenant.finance.wallet.transactions.data`, `tenant.finance.wallet.subscribe`, `tenant.finance.wallet.subscribe.validate`, `tenant.finance.billing.data`, `tenant.finance.vendor-purchases.data`, `tenant.finance.vendor-purchases.export`, `tenant.finance.vendor-purchase-settle.pay`, `tenant.finance.vendor-purchase-settle.pay.validate`, `tenant.finance.settlement-payments.data`, `tenant.finance.payouts.data`, `tenant.finance.buy-languages.data`, `tenant.finance.buy-languages.purchase`, `tenant.finance.buy-languages.purchase.validate`.
- Declare `finance/billing/data` before `finance/billing/{orderId}` and add `->whereNumber('orderId')`.

## 10. Vite entries
```
'resources/js/tenant/pages/finance/wallet.js',
'resources/js/tenant/pages/finance/billing-index.js',
'resources/js/tenant/pages/finance/billing-show.js',
'resources/js/tenant/pages/finance/vendor-purchases.js',
'resources/js/tenant/pages/finance/vendor-settle.js',
'resources/js/tenant/pages/finance/settlement-payments.js',
'resources/js/tenant/pages/finance/payouts.js',
'resources/js/tenant/pages/finance/buy-languages.js',
```

## 11. Verification
1. Test each gateway type in sandbox:
   - Stripe inline: card element, bad card → inline error, success → redirected to charge.
   - Authorize.Net inline (sandbox → `jstest.`).
   - 2Checkout inline.
   - A redirect gateway (PayPal/Paymob/etc.) → straight redirect.
2. The session keys written are identical: compare `session()->all()` before `charge` against the Livewire version.
3. Returning from the gateway (success, cancel, error) shows the correct toast on the landing page.
4. The Stripe/Accept/2pay scripts load only after a gateway is selected, and only on pages with a payment modal. No other third-party requests.
5. All finance tables page, filter and export. The central-model tables (payouts, settlements) are scoped to `tenant_id`.
