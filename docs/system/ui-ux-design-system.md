# UI/UX and Design System (reverse-engineered)

> Part of [COMPLETE_SYSTEM_DOCUMENTATION.md](../../COMPLETE_SYSTEM_DOCUMENTATION.md) §11. Values are taken from the CSS and Blade sources listed with each table **[V]**. Visual impressions not confirmed in a browser are marked **[I]**. Before a redesign freeze, capture staging screenshots of every page listed in [storefront.md](storefront.md) and the dashboards (see the master doc §25.3).

## 1. Four interface families

| Interface | Layout file(s) | Component library | CSS entry | JS entry | Fonts |
|---|---|---|---|---|---|
| Super Admin | `resources/views/layouts/app.blade.php` + `layouts/app/{header,sidebar,icon}.blade.php` | anonymous Blade components `resources/views/components/*.blade.php` (`x-btn`, `x-card`, `x-input`, `x-select`, `x-modal`, `x-table`, `x-pagination`, `x-dropzone`, `x-editor`, `x-multi-select`, `x-paginated-searchable-select`, `x-json-tree`, `x-admin-notification-bell`, …) + Livewire views `resources/views/livewire/admin/**` | `resources/css/app.css` (5,619 lines, Tailwind v4 + custom classes) | `resources/js/app.js` | DM Sans (300–600), Syne (400–800, display) |
| Affiliate and Owner portals | `layouts/affiliate.blade.php`, `layouts/owner.blade.php`, `layouts/auth.blade.php` (shared login view `livewire.auth.panel-login`) | same as Super Admin | `app.css` | `app.js` | same |
| Vendor panel | `resources/views/tenant/layouts/{app,guest}.blade.php` + `partials/` (body `.t-scope`); the legacy `layouts/tenant.blade.php` + `layouts/tenant/*` still exist although documented as deleted | `x-tenant::*` in `resources/views/tenant/components/*` (68 entries, registered in `AppServiceProvider` via `Blade::anonymousComponentPath(..., 'tenant')`) | `resources/css/tenant/app.css` → `base/tokens.css`, `base/utilities.css`, `components/*.css` (30 files), `pages/*.css` | `resources/js/tenant/app.js` + `core/`, `components/`, `modules/`, `pages/` | Outfit; IBM Plex Sans Arabic for RTL |
| Marketing website | `layouts/website.blade.php`, `layouts/website/*` | Livewire views `resources/views/livewire/website/**` | `resources/css/website.css` | `app.js` | site font [I] |
| Storefront | `resources/views/themes/{ecommet,elora,souqify}/layout/app.blade.php` (+ `styles-vN`, `scripts-vN`) | theme partials + `components/{elora,souqify,storefront}` + Livewire ThemeKit | `elora*.css`, `elora/*.css` (header/footer/home v1–v6, category), `souqify*.css`, `ecommet.css`, `*-variants.css` | `elora.js`, `elora/*.js`, `souqify*`, `storefront-cart.js`, `image-search.js` | chosen by vendor (`App\Support\ThemeDesign\ThemeFonts`; 25 bundled `@fontsource` families incl. Arabic: Cairo, Tajawal, Almarai, Readex Pro, Noto Sans Arabic, Amiri, Lalezar, Lemonada, Reem Kufi, Scheherazade New) |

**Shared vs specific [V]:** central admin, affiliate, owner, auth and website share `app.css` + `app.js`. The vendor panel has its own bundle (it deliberately does not touch `app.css`). Storefront themes are fully separate. Only a few Blade components are shared across families: `image-search-modal`, `payment-method-icon`, `storefront-logo`, `storefront-footer-logo`.

## 2. Super Admin / Affiliate / Owner design language (`resources/css/app.css`)

### 2.1 Color tokens (`[data-theme]` on `<html>`) [V]
| Token | Dark (default look) | Light |
|---|---|---|
| `--bg` | `#070d18` | `#eef2f9` |
| `--surface` | `#0c1422` | `#ffffff` |
| `--elevated` | `#111b2a` | `#f4f7fc` |
| `--card` / `--card2` | `#141f30` / `#18243a` | `#ffffff` / `#f8faff` |
| `--border` / `--border2` | `rgba(99,130,179,.13)` / `.24` | `rgba(30,60,120,.10)` / `.22` |
| `--t1` / `--t2` / `--t3` (text) | `#ffffff` / `#e8edf7` / `#c7d1e6` | `#0a0e17` / `#2d3748` / `#6b7280` |
| `--cyan` (primary accent) | `#00e5ff` | `#0097c4` |
| `--violet` | `#a259ff` | `#7c3aed` |
| `--green` / `--amber` / `--red` | `#00e59b` / `#ffb830` / `#ff4d6a` | `#059669` / `#d97706` / `#dc2626` |
| `--logo` gradient | `linear-gradient(135deg,#00e5ff,#a259ff)` | `linear-gradient(135deg,#0097c4,#7c3aed)` |
| `--shadow` / `--shadow-sm` | `0 8px 32px rgba(0,0,0,.45)` / `0 2px 8px rgba(0,0,0,.3)` | `0 8px 32px rgba(20,50,120,.08)` / `0 2px 8px rgba(20,50,120,.06)` |
| `--input` | `rgba(17,27,42,1)` | `#eef2f9` |

### 2.2 Typography and scale [V]
- Body: DM Sans. Display headings (`.D`, `--font-display`): Syne.
- Font sizes used most: 12px (31 uses), 11px, 13px, 10px, 12.5px, 11.5px, 10.5px. This is a **dense, small-type dashboard**.
- Radii used most: 10px, 999px (pills), 20px, 8px, 12px, 7px; cards use 15px.

### 2.3 Components [V/I]
| Component | Spec |
|---|---|
| Card `.card` | `var(--card)` background, 1px `var(--border)`, radius 15px, padding 18px, `--shadow-sm`, subtle diagonal sheen (`::before` gradient); glow variants `.card-glow-{cyan,violet,green,amber}` |
| Stat cards | `.stat-head`, `.stat-value`, `.stat-icon`, `.stat-sub`, trend pills `.stat-pill-pos/neg` |
| Buttons `.btn` | padding 7px 13px, radius 8px, 12.5px, weight 500; `.btn-primary` uses the `--logo` gradient; `.btn-secondary` elevated; `.btn-danger`, `.btn-sm`; social buttons `.btn-social(-apple)` |
| Tables | `.table-card-shell`, header shell with `.table-search` + actions, `.table-scroll-wrap`, footer pagination (`x-pagination`, `x-link-pagination`), inline actions |
| Modals | `.modal-card`, `.modal-body-shell`, `.modal-footer-actions` (Livewire `show*Modal` flags) |
| Badges / status | `.badge`, `.status-indicator` |
| Navigation | left sidebar grouped by sections → collapsible groups → links (`nav-icon-btn`, icons by key: dashboard, products, orders, shipping, plans, manufacturing, payments, notifications, admins, wallet, affiliates, blog, domains, pages, faq, email, settings, cache); breadcrumb section › group › page; top bar with bell + theme toggle |
| Forms | `x-input`, `x-select`, `x-textarea`, `x-checkbox`, `x-multi-select`, `x-dropzone` (uploads), `x-editor` (TinyMCE), locale tabs on translatable forms; intl-tel-input styled per theme (lines 5480–5500) |
| Feedback | toastr/SweetAlert2 confirmations; Livewire loading states [I] |

## 3. Vendor panel design system (`resources/css/tenant/**`, scope `.t-scope`)

### 3.1 Tokens (`base/tokens.css`) [V]
| Group | Tokens |
|---|---|
| Brand | `--ds-brand-50 #FFF1E6`, `-100 rgba(253,111,4,.12)`, `-200 #FED2B1`, **`-500 #FD6F04`**, `-600 #E66504`, `-900 #8B3D02`, `--ds-on-brand #F5F5F5` |
| Gradient | `--ds-gradient: linear-gradient(90deg,#612CFF 0%,#FF8A65 100%)`; `--ds-gradient-soft` 12% alpha |
| Neutrals | white `#FFFFFF`, gray-50 `#F9F9F9`, 100 `#ECECEC`, 200 `#D9D9D9`, 300 `#C1C1C1`, 400 `#A3A3A3`, 500 `#787878`, 600 `#5E5E5E`, 900 `#1E1E1E`, black `#0B0B0B` |
| Status | success `#03AA00` / bg `#E6FEE6`; danger `#E00000` / `#FCE6E6`; warning `#F0C800` / `#FEFAE6`; info `#424AFF` / `#E6E7FE` |
| Semantic | `--ds-bg` (gray-50; **mobile light `#FFF5F3`**), `--ds-surface`, `--ds-text`, `--ds-text-muted`, `--ds-border` (gray-300), `--ds-input-bg` (gray-100), `--ds-nav-idle` (brand-50), `--ds-nav-hover` (brand-200), `--ds-sb-card #F5F5F5` |
| Dark overrides | bg `#0f0f10`, surface `#18181a`, border `#3a3a3d`, input `#232326`, text `#f5f5f5` |
| Type | `--ds-font: "Outfit"`; `--ds-font-ar: "IBM Plex Sans Arabic"` applied under `[dir="rtl"]` |
| Radii / effects | `--ds-radius-btn: 0px` (**square buttons**), `--ds-radius-md: 12px`, `--ds-radius-pill: 40px`, `--ds-shadow-menu: 0 0 11.3px rgba(0,0,0,.08)`, `--ds-focus-ring: 0 0 0 3px rgba(253,111,4,.18)` |
| Legacy aliases | `--cyan`, `--violet`, `--logo` → brand orange (so old classes adopt the new brand) |

### 3.2 Components (`components/*.css` + `x-tenant::*`) [V]
| Component | Spec |
|---|---|
| Button (`x-tenant::btn`, `x-tenant::submit`, `.btn`) | sizes: default 40px (padding 8×16, 16px/500), `.btn-md` 48px, `.btn-lg` 56px, `.btn-sm` 32px (13px); variants primary (brand-500 → hover 600 → active 900), secondary (outline, hover brand-50), ghost, danger (hover danger-bg); focus outline 2px brand; square corners |
| Field (`x-tenant::field`, `input`, `select`, `select2`, `phone`, `date`, `daterange`, `color`, `file`, `image-upload`, `editor`, `radio(-group)`, `checkbox(-group)`, `repeater`, `schema-fields`, `locale-tabs`/`locale-pane`) | label 14px; control min-height 48px, radius 12px, 14px text, gray-100 fill turning white when active; textarea min 96px; required marker |
| Modal (`x-tenant::modal`, `[data-tenant-modal]`) | centered card with shadow; **becomes a bottom sheet under 640px** (full width, radius 16px 16px 0 0) |
| Toast (`toast.css`) | radius 10px, colored side border (cyan, green, red, amber; mirrored in RTL) |
| Data table (`x-tenant::datatable`, `ds-table.css`, `datatable.css`) | DataTables 2 + Responsive; server side; column definitions from PHP `TableColumn`; cell partials `_cols/*.blade.php`; `x-tenant::filters-card` with URL-synced filters; `x-tenant::pagination` |
| Stats (`x-tenant::stats-grid`, `stat-card`, `ds-kpi.css`) | KPI cards built by `Metric::cards()` |
| Page header (`x-tenant::page-header`) | title, subtitle, actions |
| Feedback | `x-tenant::alert`, `empty-state`, `skeleton` (loading), `progress`, `status-badge`, `status-toggle`, SweetAlert2 confirms (`data-confirm`) |
| Navigation | sidebar (`sidebar.css`: nav-idle/hover brand washes, section labels, groups, unread badges), header (`header.css`: locale switch, theme toggle, notification bell, account), `mobile-nav.css` (mobile drawer/bottom nav [I]), setup-progress widget |
| Misc | `copy`, `kv`/`kv-row`, `json-tree`, `chart` (Chart.js), `chat` (request chats), `country-picker`, `country-scope`, `flag`, `sortable-list` (SortableJS), `dropdown`/`dropdown-item`, `select-menu`, `tooltip`, `gradients`, `logo-builder`, `payment`, `payment-readiness`, `compliance`, `image-search` |

### 3.3 Interaction conventions [V `docs/tenant-panel-refactor.md`]
- Declarative `data-*` wiring (`data-modal-open`, `data-confirm`, `data-mode="create|edit"`); per-page JS only when needed.
- Validate-on-blur via `POST …/validate` endpoints; the first invalid field is focused, and the locale tab holding an error is revealed.
- Global HTTP client handles 401 (to login), 403 (toast), 409 (setup redirect), 419 (reload), 429 and 5xx.
- No CDN and no inline script/style, except payment SDKs, JSON islands, sortable lists and static breakdown tables.

## 4. Storefront theme design (summary) [V/I]

| Theme | Palette (from CSS variables) | Character |
|---|---|---|
| Elora (v1–v6) | primary `#ff4d00`, secondary `#ff522c`, bg `#fdfdfd`, text `#121212`, subtitle `#adadad`, gray `#8f8f8f`, stroke `#d4d4d4`, black `#242424` / `#1b1b1b`, success `#2aaf2f`, error `#de1709`, accent purple `#8a38f5` / `#51218f` | bold orange retail; category circles/strips, tabbed product rails, trending, flash strips; 6 header/footer/home variants |
| Souqify (v1–v6) | same base palette as Elora v2 (`#FF4D00` primary) plus purple deal cards (`purple_deal_card`) | marketplace style: hero banners, browse categories, hot deals, special offers, top rated |
| Ecommet | success `#2aaf2e`, purple `#61167f`, grays `#232323 → #f5f5f5`, sale light `#ffecea`, yellow light `#fdff9f` | promotional, "lightning deals", showcase cards |

- **Vendor overrides:** `tenant_theme_colors` (Appearance → colors, reset), fonts (LTR/RTL font pickers), logo builder, promo banner, top-header links, footer style, home variant colors (`home_variants.colors`), and per-country theme and variant.
- **Shared storefront components:** product card (image, badge/label ribbon, favorite heart, price + strikethrough, stars, add-to-cart), carousel nav (`_carousel-nav`), pagination, stars, address modal, country select, image-search modal, locale switcher, cart dropdown.
- **Breakpoints observed** (most frequent media queries across the CSS): `max-width` 1023px, 767px, 479px, 639px; `min-width` 1024px; plus 1279px and 640px.

## 5. Inconsistencies and rebuild guidance [R]
1. Three token systems (admin neon dark, vendor orange/square, storefront per theme) with different fonts and radii. Keep the **brand differences on purpose** (platform vs vendor vs shop), but implement them as **one token schema** (`--color-*`, `--radius-*`, `--font-*`, `--space-*`) with a theme file per surface.
2. Build one Blade component library (`x-ui::*`) used by admin, vendor, affiliate and owner, with surface themes. Today the admin `x-btn` and the vendor `x-tenant::btn` are separate implementations.
3. Storefront: replace copied v1–v6 files with section components that take a `variant` prop. Keep the section keys from `SectionRegistry` so existing vendor configurations migrate 1:1.
4. Retire Tailwind v3 (`resources/tailwind-v3`) and keep Tailwind v4 utilities plus component CSS.
5. Accessibility gaps to check during the rebuild: focus states exist on vendor buttons only; small 10–12px type in the admin; color contrast of `--t3` on dark cards [I].
