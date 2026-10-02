# nogrgr-new.com — SiteScan Audit Tasks
**Prepared for:** سيد باسم  
**Date:** 2026-09-20  
**Total Issues:** 44 | **Critical/High:** 2 | **Fix Progress:** 0%  
**Platform:** Multi-vendor SaaS (Souqify themes)

---

## Legend
| Status | Meaning |
|--------|---------|
| ✅ Done | Fixed and verified |
| 🔴 Critical | Must fix before launch |
| 🟡 Medium | Fix in next sprint |
| ⬜ Pending | Not started |

---

## 🔴 CRITICAL — Fix Immediately (2 issues)

---

### CRITICAL-01 · 500 Error — Order Page "View" Button
**Section:** Merchant Dashboard → Orders  
**Status:** ⬜ Pending  
**Type:** Server Error  

**Problem:** Clicking "View" on any order in the Orders section throws a 500 error and does not open order details.

**Sub-agent Prompt:**
```
In the multi-vendor Laravel/PHP application at /var/www/multi-vendor:
Investigate and fix the 500 server error that occurs when a merchant clicks the "View" button on an order in the merchant dashboard Orders section.
Steps:
1. Check Laravel logs in storage/logs for the 500 error trace.
2. Locate the Orders controller and the show/view route for merchant orders.
3. Identify whether the issue is a missing relationship, null reference, authorization policy, or missing DB column.
4. Fix the root cause and confirm the order detail page loads correctly for a test order.
5. Add a regression test if a test suite exists.
```

---

### CRITICAL-02 · 500 Error — Delivery Orders "View" Button
**Section:** Merchant Dashboard → Delivery Orders  
**Status:** ⬜ Pending  
**Type:** Server Error  

**Problem:** The "View" button in Delivery Orders section does not open order details and throws a server error.

**Sub-agent Prompt:**
```
In the multi-vendor Laravel/PHP application at /var/www/multi-vendor:
Investigate and fix the 500 server error on the "View" button in the Delivery Orders section of the merchant dashboard.
Steps:
1. Check storage/logs for the error stack trace.
2. Find the DeliveryOrders controller and its show/view method.
3. Determine if the issue is a missing eager-load, policy gate, or route binding problem.
4. Fix the issue and verify delivery order details load correctly.
5. Confirm CRITICAL-01 and CRITICAL-02 share or differ in root cause and document findings.
```

---

## 🟡 ADMIN PANEL — Issues (2 issues)

---

### ADMIN-01 · Category Image Not Saved on Edit
**Section:** Admin Dashboard → Categories  
**Status:** ⬜ Pending  
**Type:** Other / Medium  

**Problem:** When editing a category from the admin panel and uploading a new image, the image is not accepted or saved correctly.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the bug where editing a Category from the admin panel and attaching a new image fails to save the image.
Steps:
1. Find the Category update controller/action and its form request validation.
2. Check if the image field is included in the fillable/update logic.
3. Verify the file upload handler (disk, path, validation rules) for category images.
4. Check if the issue is a missing enctype on the form or a missing @method('PUT') directive.
5. Fix and test by updating a category image end-to-end.
```

---

### ADMIN-02 · Sync Operation Resets User/Store Theme Settings
**Section:** Admin Dashboard → Sync  
**Status:** ⬜ Pending  
**Type:** Other / Medium  

**Problem:** Running Sync from the admin panel resets all Users' theme settings to default. Sync must preserve existing user/store settings.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the Sync operation so it no longer resets User and Store theme settings.
Steps:
1. Locate the Sync command/job/controller (search for 'sync' in app/Console/Commands, app/Jobs, app/Http/Controllers).
2. Identify what data is being overwritten — likely a mass-update or truncate/re-seed of a settings or user_settings table.
3. Change the sync logic to use upsert (insert-or-update) rather than wipe-and-replace for user/store settings.
4. Ensure the sync only touches data it owns (e.g., product catalogue, inventory) and leaves user preferences/theme settings untouched.
5. Write a test or manual verification steps to confirm settings survive a sync.
```

---

## 🟡 MERCHANT DASHBOARD — Issues (19 issues)

---

### MERCHANT-01 · Onboarding Status Shows "Incomplete" After Full Setup
**Section:** Merchant Dashboard → Onboarding → Profile & Store Identity  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** All required fields in Profile & Store Identity are filled and saved, but the system still shows the step as "Incomplete".

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the onboarding completion check so that "Profile & Store Identity" is marked complete once all required fields are saved.
Steps:
1. Find the onboarding/setup progress calculation (search for 'onboarding', 'setup', 'incomplete', 'progress' in app/).
2. Check the list of required fields being validated for completion vs. what the frontend fills in.
3. Fix the mismatch — either the required-fields list is wrong, or the DB check query is incorrect.
4. Verify the step shows "Complete" after saving valid data for all fields.
```

---

### MERCHANT-02 · Country Code Not Auto-Selected on Registration
**Section:** Merchant Dashboard → Register  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** When registering, the phone country code is not automatically set based on the selected country — user must choose it manually.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Auto-populate the phone country code field based on the selected country during merchant registration.
Steps:
1. Locate the registration form/view (resources/views or frontend JS/Vue/React component).
2. Find the country selector and country-code selector fields.
3. Add a JS event listener: on country change, look up the country's dial code from a country-to-dial-code map and set the country code field automatically.
4. Ensure this works for both create-new and edit flows.
5. Test with multiple countries.
```

---

### MERCHANT-03 · Logo Disappears When Navigating to Payment/Store Details in First Setup
**Section:** Merchant Dashboard → First Setup  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Logo is uploaded successfully in the first step of setup but disappears when the user navigates to the Payment or Store Details step.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the logo persistence bug in the multi-step First Setup wizard.
Steps:
1. Locate the multi-step setup wizard (controller + views/components).
2. Determine if the logo is stored in session, DB, or only in the form state.
3. If session-based: ensure the logo path/value is carried through all steps and not cleared on step transitions.
4. If DB-based: ensure the logo is saved immediately on upload (AJAX) rather than on final form submit.
5. Verify the logo remains visible across all setup steps.
```

---

### MERCHANT-04 · English Language Setting Does Not Apply as Default
**Section:** Merchant Dashboard → Language Settings  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Selecting English as the default language for the storefront does not take effect.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the default language setting so selecting English correctly applies it as the storefront default.
Steps:
1. Find the language setting save handler and where the default locale is stored (DB column or config).
2. Check where the storefront reads the default locale — is it reading from the right DB field?
3. Fix the disconnect between the saved setting and the storefront locale resolution.
4. Test by setting English, clearing cookies/session, and loading the storefront.
```

---

### MERCHANT-05 · Unused Home Page Sections Appear in Admin Options
**Section:** Merchant Dashboard → Home Page Sections  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Sections not used by the current theme still appear in the Home Page Sections settings (e.g., "category carousel (static)", "shop by category", "orange category strip"). These need to be filtered or explained.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Filter Home Page Sections options so only sections supported by the active theme are shown in the admin UI.
Steps:
1. Find where Home Page Sections are defined and rendered in the admin (controller + view).
2. Find the theme configuration that defines supported sections per theme.
3. Add a filter: only show sections that are registered in the active theme's config.
4. For sections that apply to multiple themes, add a tooltip/label indicating which themes support them.
5. Test across at least 3 themes to confirm correct filtering.
```

---

### MERCHANT-06 · Country-Specific Product Sections Not Applying (Best Selling, New In, Featured, etc.)
**Section:** Merchant Dashboard → Home Page Sections  
**Status:** ⬜ Pending  
**Type:** Accessibility / Medium  

**Problem:** Selecting products for Best Selling, New In, Featured, Recommended, and Trending Now by country does not apply — the same products show regardless of country.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix country-specific product section assignments (Best Selling, New In Products, Featured Products, Recommended Products, Trending Now).
Steps:
1. Locate the query/repository that fetches products for each of these sections on the storefront.
2. Check if it filters by the current user's country or if it ignores the country setting entirely.
3. Find the admin save handler for these section settings and confirm the country is stored correctly.
4. Fix the storefront query to join/filter on the saved country assignment.
5. Test by assigning different products to two countries and verifying each country sees the correct set.
```

---

### MERCHANT-07 · Cannot Assign a Theme Per Country
**Section:** Merchant Dashboard → Theme Settings  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** There is no way to assign a specific theme to a specific country.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Implement per-country theme assignment in the merchant dashboard.
Steps:
1. Check if the DB schema has a country field on the theme settings/store_themes table.
2. If not, add a migration: store_theme_countries (store_id, theme_id, country_code).
3. Update the theme settings UI to allow selecting a theme per country (dropdown per country row).
4. Update the storefront theme resolver to pick the correct theme based on the visitor's detected country.
5. Fall back to the store default theme if no country-specific theme is set.
```

---

### MERCHANT-08 · Coupons Don't Work When Currency Is Not USD
**Section:** Merchant Dashboard → Coupons  
**Status:** ⬜ Pending  
**Type:** Performance / Medium  

**Problem:** Coupons function correctly only when the cart currency is USD. They fail with other currencies.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix coupon validation and application logic to work correctly with non-USD currencies.
Steps:
1. Find the coupon validation/apply service/controller.
2. Check if the discount amount comparison is currency-aware or if it hardcodes USD amounts.
3. Ensure coupon minimum order values and discount amounts are converted to the active currency before comparison.
4. Fix the calculation and test with at least 2 non-USD currencies (e.g., EUR, SAR).
```

---

### MERCHANT-09 · Flash Sale Not Visible After Creation for a Specific Country
**Section:** Merchant Dashboard → Flash Sales  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Creating a Flash Sale targeting a specific country does not make it appear on the storefront.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix Flash Sale visibility for country-targeted sales.
Steps:
1. Find the Flash Sale creation handler and confirm the country is saved to the DB.
2. Find the storefront query that loads active Flash Sales and check if it filters by country.
3. Also check activation status, date range, and any caching that might prevent immediate display.
4. Fix the query and invalidate/clear cache after a Flash Sale is created.
5. Test by creating a Flash Sale for a specific country and verifying it appears on that country's storefront.
```

---

### MERCHANT-10 · Banner Info Not Displayed After Creation
**Section:** Merchant Dashboard → Banners  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Banner data entered (text, button, etc.) is saved but not rendered on the storefront banner.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the Banner component to display all saved banner fields (title, subtitle, button text, link) on the storefront.
Steps:
1. Find the Banner model and its DB fields.
2. Find the storefront banner view/component and check which fields it renders.
3. Compare rendered fields vs. saved fields — identify what is missing.
4. Fix the view to output all relevant banner fields.
5. Test by creating a banner with all fields filled and verifying each field displays correctly.
```

---

### MERCHANT-11 · Notifications Not Appearing in User & Admin Dashboards
**Section:** Both Dashboards → Notifications  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** New Order, Return, New Customer, and other notifications do not appear in either the User Dashboard or Admin Dashboard.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the notification system so that New Order, Return, and New Customer events trigger visible notifications in both User and Admin dashboards.
Steps:
1. Find the notification classes/listeners for these events (app/Notifications, app/Listeners).
2. Check if notifications are being dispatched — add a temporary log to confirm.
3. Check the notification storage (DB table 'notifications') — are rows being created?
4. Check the dashboard notification reader — is it querying the correct notifiable type/id?
5. Fix the broken link in the chain (event → listener → notification → dashboard display).
```

---

### MERCHANT-12 · "Get Started" Button Shows After Onboarding is Complete
**Section:** Merchant Dashboard → Navigation  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** "Get Started" button continues to appear in the main menu after the merchant completes all onboarding steps. It should only appear once during initial setup.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Hide the "Get Started" button and menu item once the merchant has completed onboarding.
Steps:
1. Find where the "Get Started" button/menu item is rendered (layout or navigation component).
2. Find the onboarding completion flag on the Store/User model.
3. Add a condition: only render "Get Started" if onboarding is not complete.
4. Also remove it from the sidebar/main menu once complete.
5. Test: complete onboarding on a test store and confirm the button disappears.
```

---

### MERCHANT-13 · Template Live Preview Not Working
**Section:** Merchant Dashboard → Templates  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The live preview of templates does not work.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the Templates live preview feature in the merchant dashboard.
Steps:
1. Find the template preview route and controller method.
2. Check if the preview URL is being constructed correctly (correct store subdomain/slug, theme parameter).
3. Check browser console errors on the preview load — likely a broken iframe src, CORS issue, or missing route.
4. Fix the preview rendering and test with each available template.
```

---

### MERCHANT-14 · Mobile Banner Shows Without Text/Button in "Default Souqify" Theme
**Section:** All Themes → Default Souqify  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** On mobile, the banner in "Default Souqify" theme renders without its text and button.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the mobile banner rendering in the "Default Souqify" theme to display text and button correctly.
Steps:
1. Open the Default Souqify banner blade/component file.
2. Check if the text/button elements are hidden via CSS media queries or conditionally rendered based on a mobile detection flag.
3. Fix the CSS or template logic so text and button are visible on mobile.
4. Test on multiple mobile viewport sizes (375px, 390px, 414px).
```

---

### MERCHANT-15 · Store Name Not Used as Default Text Logo
**Section:** Merchant Dashboard → Store Setup  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** After entering the store name during setup, the text logo defaults to "My Store" instead of the entered name.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Auto-populate the text logo with the store name entered during initial setup.
Steps:
1. Find where the text logo is initialized/stored (store settings table).
2. Find the store creation/setup handler.
3. After the store name is saved, also set the text_logo field to the same value if it hasn't been customized.
4. Verify: creating a new store with name "Test Shop" results in text logo defaulting to "Test Shop".
```

---

### MERCHANT-16 · Product Categories Selection Missing from Registration
**Section:** Merchant Dashboard → Register  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The registration flow does not include a step to select product categories.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Add a Product Categories selection step to the merchant registration flow.
Steps:
1. Find the merchant registration form/controller.
2. Add a categories multi-select field populated from the main categories table.
3. Save the selected categories to the store's preferred_categories (or equivalent) field/pivot table.
4. Make the field optional with a "skip" option so registration is not blocked.
5. Test registration with and without category selection.
```

---

### MERCHANT-17 · Brand Requests — Slow Response Time
**Section:** Merchant Dashboard → Brand Requests  
**Status:** ⬜ Pending  
**Type:** Performance / Medium  

**Problem:** Brand Requests section has a noticeable delay when executing a request.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Investigate and optimize the Brand Requests response time in the merchant dashboard.
Steps:
1. Find the BrandRequests controller and its index/store methods.
2. Check for N+1 queries using Laravel Debugbar or by logging queries.
3. Add eager loading for any missing relationships.
4. Check if the action triggers any synchronous external API calls that could be made async (queued job).
5. Target response time under 500ms for the request action.
```

---

## 🟡 ALL THEMES — Issues (23 issues)

---

### THEME-01 · Cart Quantity +/− Buttons Are Slow to Respond
**Section:** All Themes → Cart  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Clicking + or − to change quantity in the cart has noticeable lag before the UI updates.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the cart quantity update performance across all themes.
Steps:
1. Find the cart quantity update handler (AJAX/Livewire/Vue component).
2. Check if the UI waits for a server round-trip before updating the displayed quantity.
3. Implement optimistic UI: update the displayed quantity immediately, then sync with the server in the background.
4. Also check for debounce on rapid +/− clicks to prevent multiple concurrent requests.
5. Test on slow network (throttle to 3G in DevTools) to confirm acceptable UX.
```

---

### THEME-02 · "Set Default Address" Not Working
**Section:** All Themes → User Account → Addresses  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The "Set Default Address" function does not work correctly.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the "Set Default Address" feature in the user account address management.
Steps:
1. Find the address controller's setDefault method.
2. Check if it correctly sets is_default=true on the target address and false on all others for the same user.
3. Verify the response updates the UI to reflect the new default.
4. Test: set a non-default address as default and confirm it persists on page reload.
```

---

### THEME-03 · "Contact Us" Link in Order Tracking Goes to Wrong Page
**Section:** All Themes → Order Tracking  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The "Contact Us" link on the Order Tracking page should route to the store owner's contact page, not a generic one.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the "Contact Us" link in the Order Tracking page to point to the store owner's contact page.
Steps:
1. Find the Order Tracking view/component.
2. Find where the "Contact Us" link href is set.
3. Replace the static/generic link with a dynamic one that resolves to the current store's contact URL.
4. Test from a store's order tracking page to confirm the link goes to that store's contact page.
```

---

### THEME-04 · "Recommended for You" Section Is Static, Not Personalized
**Section:** All Themes → Home Page  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The "Products Recommended for You" section shows the same static list regardless of user behavior. It should update based on viewed/carted products.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Implement dynamic product recommendations based on user interaction for the "Recommended for You" section.
Steps:
1. Find the recommendations query on the home page controller/component.
2. Track user product views and cart additions (store in session or user_interactions table).
3. Update the recommendations query to prioritize products from the same categories as recently viewed/carted items.
4. If the user has no history, fall back to globally popular/featured products.
5. Test: view products in category A, reload home page, verify recommended section shows more category A products.
```

---

### THEME-05 · RTL Layout — Images Misaligned in Category Sections
**Section:** All Themes → RTL Interface  
**Status:** ⬜ Pending  
**Type:** Accessibility / Medium  

**Problem:** In RTL mode, images in category sections do not appear in their correct positions.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix category section image alignment in RTL layout across all themes.
Steps:
1. Enable RTL on a test store and navigate to the category section on the storefront.
2. Inspect the misaligned images in browser DevTools.
3. Check the CSS — likely missing `[dir="rtl"]` overrides or incorrect use of `float` vs. `flexbox` in RTL.
4. Fix the RTL CSS so images appear in the correct position matching the LTR layout mirrored for RTL.
5. Test across at least 3 themes.
```

---

### THEME-06 · Currency Selector Not Visible on Mobile
**Section:** All Themes → Mobile  
**Status:** ⬜ Pending  
**Type:** Accessibility / Medium  

**Problem:** The currency selector does not appear when accessed from a mobile device.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the currency selector visibility on mobile across all themes.
Steps:
1. Inspect the currency selector component in DevTools at mobile viewport (375px).
2. Check if it is hidden via CSS (display:none or visibility:hidden) at mobile breakpoints.
3. Add the currency selector to the mobile navigation menu or make it accessible via a dedicated mobile UI element.
4. Test on 375px, 390px, and 414px viewports.
```

---

### THEME-07 · "Shop by Category" Section Incomplete — Not All Categories Shown
**Section:** All Themes → Home Page  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The "Shop by Category" section does not display all available categories.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the "Shop by Category" section to display all active categories.
Steps:
1. Find the query that fetches categories for the "Shop by Category" section.
2. Check if there is a hardcoded LIMIT or a per-page restriction cutting the list.
3. Check if inactive/hidden categories are incorrectly excluded or vice versa.
4. Fix the query to return all active, publicly visible categories.
5. If the section has a "view all" pagination, verify it works as well.
```

---

### THEME-08 · Best Seller Products — Hover Animation Not Working
**Section:** All Themes → Best Seller Products Section  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Hovering over products in the Best Seller section triggers no animation. The product card should appear elevated (z-index raise) above other products on hover.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the hover animation on Best Seller product cards so the hovered card lifts above others.
Steps:
1. Find the Best Seller products section CSS/component.
2. Add or fix the CSS hover rule: `transform: translateY(-8px); z-index: 10; box-shadow: 0 12px 30px rgba(0,0,0,0.15);` with `transition: all 0.2s ease;`.
3. Ensure the parent container has `overflow: visible` so raised cards are not clipped.
4. Test across themes and verify smooth animation on hover.
```

---

### THEME-09 · Flash Sale Section — Images Too Small + Missing Button
**Section:** All Themes → Flash Sale Section (Section 12)  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Flash Sale product images are too small relative to the section, and a button is missing.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix Flash Sale section image sizing and add the missing CTA button.
Steps:
1. Find the Flash Sale section view/component.
2. Increase product image dimensions to match the design spec (check Figma if available).
3. Add a "Shop Now" or "View Deal" button below the image/product info.
4. Ensure the button links to the product detail page.
5. Test on desktop and mobile viewports.
```

---

### THEME-10 · Categories Section Missing Internal Padding
**Section:** All Themes → Categories Section  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The Categories section has no internal padding (padding-inside), making content touch the edges.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Add correct internal padding to the Categories section across all themes.
Steps:
1. Find the Categories section CSS class/component.
2. Add appropriate padding (e.g., `padding: 16px 20px` or match Figma spec).
3. Ensure padding is consistent on desktop and mobile.
4. Verify it does not break other section layouts.
```

---

### THEME-11 · Footer Uses Global Logo Instead of Dedicated Footer Logo
**Section:** All Themes → Footer  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The footer always uses the main store logo. A separate footer logo upload option is needed.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Add a dedicated footer logo setting and use it in the footer across all themes.
Steps:
1. Add a `footer_logo` column to the store_settings table (migration).
2. Add a "Footer Logo" upload field in the merchant dashboard under Store Identity / Theme Settings.
3. Update the footer view to use `footer_logo` if set, else fall back to the main `logo`.
4. Test: upload a different image as footer logo and confirm it appears only in the footer.
```

---

### THEME-12 · Promo Banner — No Image Upload Option; No Mobile Image Field
**Section:** All Themes → Promo Banner Settings  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The Promo Banner settings have no image upload field and no separate mobile image field.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Add desktop and mobile image upload fields to the Promo Banner settings.
Steps:
1. Find the Promo Banner settings form in the merchant dashboard.
2. Add two file upload fields: "Desktop Image" and "Mobile Image".
3. Add image dimension guidance/validation (e.g., desktop: 1920×600, mobile: 768×400).
4. Save both images to the promo_banners table.
5. Update the storefront Promo Banner component to serve the mobile image on small viewports and desktop image otherwise.
```

---

### THEME-13 · "Elora Fresh Edition" — Not All Categories Displayed
**Section:** Theme: Elora Fresh Edition  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The Elora Fresh Edition theme does not display all available categories.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the category display in the "Elora Fresh Edition" theme to show all active categories.
Steps:
1. Find the Elora Fresh Edition theme's category section component/view.
2. Check if there is a hardcoded limit or a theme-specific category filter.
3. Remove or increase the limit so all active categories render.
4. Test and compare with another theme to confirm parity.
```

---

### THEME-14 · Image Search Returns No Results
**Section:** All Themes → Search  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Searching by image does not return correct results, blocking visual product discovery.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the image search feature to return relevant product results.
Steps:
1. Find the image search controller/service.
2. Check if the external image recognition API (if used) is configured correctly and the API key is valid.
3. Check if the image is being sent to the API correctly (format, size, encoding).
4. Verify the returned tags/labels are being mapped to product queries correctly.
5. Test with a clear product image and confirm matching products appear in results.
```

---

### THEME-15 · Default Souqify — Top Header Links Not Customizable
**Section:** Theme: Default Souqify → Top Header  
**Status:** ⬜ Pending  
**Type:** Accessibility / Medium  

**Problem:** The Top Header in Default Souqify has hardcoded links ("ابحث عن متجر", "المتجر"). Merchants need to customize these links from their dashboard.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Make the Top Header links in "Default Souqify" customizable from the merchant dashboard.
Steps:
1. Find the Default Souqify top header view/component.
2. Identify the hardcoded links.
3. Add a "Top Header Links" settings section in the merchant dashboard (up to N links with label + URL fields).
4. Save to a theme_settings JSON column or dedicated table.
5. Render the saved links dynamically in the top header, falling back to defaults if none are set.
```

---

### THEME-16 · Hardcoded Banner Text "The fastest way to take a customizable screenshot" Appears in All Themes
**Section:** All Themes → Banner  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** A default/placeholder text "The fastest way to take a customizable screenshot" appears in banners across all themes with no way to remove or edit it.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Remove or make editable the hardcoded banner placeholder text "The fastest way to take a customizable screenshot".
Steps:
1. Search the codebase for the exact string "The fastest way to take a customizable screenshot".
2. Replace the hardcoded string with a dynamic field loaded from the banner settings.
3. Set the default value to an empty string (not the placeholder text).
4. Verify the text no longer appears on any theme when no custom text is set.
```

---

### THEME-17 · Souqify Green Edition — "Shop by Category" Section Missing from Dashboard
**Section:** Theme: Souqify Green Edition → Dashboard Settings  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The "Shop by Category" section is not available in the Home Page Sections settings for the Souqify Green Edition theme.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Add "Shop by Category" to the Home Page Sections settings for the Souqify Green Edition theme.
Steps:
1. Find where Home Page Sections are registered per theme (config or DB).
2. Add "shop_by_category" to the Souqify Green Edition's section list.
3. Ensure the corresponding front-end component is wired to the section toggle.
4. Test: enable/disable "Shop by Category" from the dashboard and verify it appears/disappears on the storefront.
```

---

### THEME-18 · Souqify Green Edition — Not Responsive on Mobile
**Section:** Theme: Souqify Green Edition → Mobile  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Souqify Green Edition does not render correctly on mobile devices.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the mobile responsiveness of the "Souqify Green Edition" theme.
Steps:
1. Open the theme on a 375px viewport and list all visual/layout breakages.
2. Fix CSS media queries for the affected components (navbar, hero, product grid, footer).
3. Ensure text is readable (min 14px), touch targets are min 44×44px, and no horizontal scroll.
4. Test on 375px, 390px, 414px, and 768px viewports.
```

---

### THEME-19 · Product Star Ratings Show Incorrect/Unrealistic Values
**Section:** All Themes → Product Cards  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Product star ratings are not based on actual review data and show unrealistic values.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix product star ratings to display values calculated from actual reviews.
Steps:
1. Find the product card view/component and where the star rating value comes from.
2. Check if rating is hardcoded, randomly generated, or actually computed from reviews.
3. Fix: compute the average rating from the product_reviews table and cache it on the Product model (`avg_rating` column or accessor).
4. Update the product card to use this computed value.
5. Products with no reviews should show no stars (not a default 5-star or random value).
```

---

### THEME-20 · Souqify Modern Edition — Deviates from Figma Design
**Section:** Theme: Souqify Modern Edition  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The implemented Souqify Modern Edition theme has visual differences from the approved Figma design.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Align the "Souqify Modern Edition" theme implementation with the approved Figma design.
Steps:
1. Open the Figma design for Souqify Modern Edition and the live theme side by side.
2. Document all visual differences (typography, spacing, colors, component layout).
3. Fix each difference in the theme CSS/components.
4. Re-compare and confirm all sections match Figma.
5. Pay special attention to the homepage hero, product grid, and navigation.
```

---

### THEME-21 · Orange Edition — "Shop by Category" Doesn't Match Figma
**Section:** Theme: Orange Edition → Shop by Category  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** The "Shop by Category" section in Orange Edition differs from the Figma design.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix the "Shop by Category" section in "Orange Edition" to match the Figma design.
Steps:
1. Compare the Figma spec for Orange Edition's "Shop by Category" with the current implementation.
2. Fix layout, typography, image sizes, and spacing to match Figma.
3. Test on desktop and mobile.
```

---

### THEME-22 · Orange, Pink, and Purple Editions — Not Responsive on Mobile
**Section:** Themes: Souqify Orange Edition, Pink Edition, Purple Edition  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** Three theme variants (Orange, Pink, Purple) do not render correctly on mobile.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix mobile responsiveness for Souqify Orange Edition, Pink Edition, and Purple Edition themes.
Steps:
1. Test each theme at 375px viewport and list all layout breakages per theme.
2. Fix CSS breakpoints for each theme's affected sections.
3. Ensure no horizontal overflow, readable font sizes, and correct stacking of elements on mobile.
4. Test all three themes at 375px and 768px viewports before marking complete.
```

---

### THEME-23 · Pink Edition — Fonts and Images Outside Frame (vs. Figma)
**Section:** Theme: Souqify Pink Edition  
**Status:** ⬜ Pending  
**Type:** UI/UX / Medium  

**Problem:** In the Pink Edition theme, fonts and images overflow outside their containers, deviating from the Figma design.

**Sub-agent Prompt:**
```
In /var/www/multi-vendor:
Fix font and image containment in the "Souqify Pink Edition" theme to match the Figma design.
Steps:
1. Open the Figma spec for Pink Edition and identify the sections where fonts/images overflow.
2. In the theme CSS/components, fix overflow rules (`overflow: hidden`), max-width constraints, and line-height/font-size to match spec.
3. Verify no text or image escapes its container on desktop and mobile.
4. Cross-check all sections against Figma before marking complete.
```

---

## Summary Table

| ID | Section | Title | Severity | Status |
|----|---------|-------|----------|--------|
| CRITICAL-01 | Merchant Dashboard | 500 Error — Order View | 🔴 Critical | ⬜ Pending |
| CRITICAL-02 | Merchant Dashboard | 500 Error — Delivery Orders View | 🔴 Critical | ⬜ Pending |
| ADMIN-01 | Admin Panel | Category Image Not Saved on Edit | 🟡 Medium | ⬜ Pending |
| ADMIN-02 | Admin Panel | Sync Resets User Theme Settings | 🟡 Medium | ⬜ Pending |
| MERCHANT-01 | Merchant Dashboard | Onboarding Shows Incomplete | 🟡 Medium | ⬜ Pending |
| MERCHANT-02 | Merchant Dashboard | Country Code Not Auto-Set | 🟡 Medium | ⬜ Pending |
| MERCHANT-03 | Merchant Dashboard | Logo Disappears in First Setup | 🟡 Medium | ⬜ Pending |
| MERCHANT-04 | Merchant Dashboard | English Language Default Broken | 🟡 Medium | ⬜ Pending |
| MERCHANT-05 | Merchant Dashboard | Unused Sections in Home Page Options | 🟡 Medium | ⬜ Pending |
| MERCHANT-06 | Merchant Dashboard | Country Product Sections Not Applied | 🟡 Medium | ⬜ Pending |
| MERCHANT-07 | Merchant Dashboard | No Per-Country Theme Assignment | 🟡 Medium | ⬜ Pending |
| MERCHANT-08 | Merchant Dashboard | Coupons Fail on Non-USD Currency | 🟡 Medium | ⬜ Pending |
| MERCHANT-09 | Merchant Dashboard | Flash Sale Not Visible After Creation | 🟡 Medium | ⬜ Pending |
| MERCHANT-10 | Merchant Dashboard | Banner Info Not Displayed | 🟡 Medium | ⬜ Pending |
| MERCHANT-11 | Both Dashboards | Notifications Not Appearing | 🟡 Medium | ⬜ Pending |
| MERCHANT-12 | Merchant Dashboard | "Get Started" Persists After Onboarding | 🟡 Medium | ⬜ Pending |
| MERCHANT-13 | Merchant Dashboard | Template Live Preview Broken | 🟡 Medium | ⬜ Pending |
| MERCHANT-14 | Default Souqify | Mobile Banner Missing Text/Button | 🟡 Medium | ⬜ Pending |
| MERCHANT-15 | Merchant Dashboard | Text Logo Defaults to "My Store" | 🟡 Medium | ⬜ Pending |
| MERCHANT-16 | Merchant Dashboard | Product Categories Missing in Register | 🟡 Medium | ⬜ Pending |
| MERCHANT-17 | Merchant Dashboard | Brand Requests Slow Response | 🟡 Medium | ⬜ Pending |
| THEME-01 | All Themes | Cart +/− Slow Response | 🟡 Medium | ⬜ Pending |
| THEME-02 | All Themes | Set Default Address Broken | 🟡 Medium | ⬜ Pending |
| THEME-03 | All Themes | Order Tracking Contact Us Wrong Link | 🟡 Medium | ⬜ Pending |
| THEME-04 | All Themes | Recommendations Are Static | 🟡 Medium | ⬜ Pending |
| THEME-05 | All Themes | RTL Category Images Misaligned | 🟡 Medium | ⬜ Pending |
| THEME-06 | All Themes | Currency Selector Hidden on Mobile | 🟡 Medium | ⬜ Pending |
| THEME-07 | All Themes | Shop by Category Incomplete | 🟡 Medium | ⬜ Pending |
| THEME-08 | All Themes | Best Seller Hover Animation Broken | 🟡 Medium | ⬜ Pending |
| THEME-09 | All Themes | Flash Sale Images Too Small + No Button | 🟡 Medium | ⬜ Pending |
| THEME-10 | All Themes | Categories Section No Padding | 🟡 Medium | ⬜ Pending |
| THEME-11 | All Themes | Footer Uses Wrong Logo | 🟡 Medium | ⬜ Pending |
| THEME-12 | All Themes | Promo Banner No Image Upload | 🟡 Medium | ⬜ Pending |
| THEME-13 | Elora Fresh Edition | Not All Categories Displayed | 🟡 Medium | ⬜ Pending |
| THEME-14 | All Themes | Image Search Broken | 🟡 Medium | ⬜ Pending |
| THEME-15 | Default Souqify | Top Header Links Not Customizable | 🟡 Medium | ⬜ Pending |
| THEME-16 | All Themes | Hardcoded Placeholder Banner Text | 🟡 Medium | ⬜ Pending |
| THEME-17 | Souqify Green | Shop by Category Missing from Dashboard | 🟡 Medium | ⬜ Pending |
| THEME-18 | Souqify Green | Not Responsive on Mobile | 🟡 Medium | ⬜ Pending |
| THEME-19 | All Themes | Star Ratings Incorrect | 🟡 Medium | ⬜ Pending |
| THEME-20 | Souqify Modern | Deviates from Figma Design | 🟡 Medium | ⬜ Pending |
| THEME-21 | Orange Edition | Shop by Category Deviates from Figma | 🟡 Medium | ⬜ Pending |
| THEME-22 | Orange/Pink/Purple | Not Responsive on Mobile | 🟡 Medium | ⬜ Pending |
| THEME-23 | Pink Edition | Fonts/Images Outside Frame vs. Figma | 🟡 Medium | ⬜ Pending |

---

## Suggested Fix Order

1. **CRITICAL-01, CRITICAL-02** — 500 errors block basic order management
2. **ADMIN-02** — Sync resetting settings risks data loss on every sync
3. **MERCHANT-08** — Coupon currency bug directly impacts revenue
4. **MERCHANT-11** — Notifications are a core communication channel
5. **THEME-16** — Hardcoded placeholder text is visible to all customers
6. **THEME-02, THEME-03** — Core shopping flow bugs
7. **MERCHANT-01 → MERCHANT-17** — Onboarding/dashboard UX
8. **THEME-01 → THEME-23** — Theme polish and Figma alignment
