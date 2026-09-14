# Prompt 02 — `x-tenant::*` Shared Component Library

Requires prompt 01. Read `prompt_00_README_tenant_panel_refactor.md` (all invariants apply).
This prompt builds the reusable component library that every tenant page (prompts 03–11) is composed from.
**No page is converted here.** Pages must not hand-write form controls, tables, modals or pagination. If a need isn't covered, add a component here first.

## 0. Registration & conventions

- Anonymous component path. In `AppServiceProvider::boot()` add:
  ```php
  Blade::anonymousComponentPath(resource_path('views/tenant/components'), 'tenant');
  ```
  Usage: `<x-tenant::input …/>`. Files live in `resources/views/tenant/components/**`.
- **Do not touch** `resources/views/components/*` (shared with the central admin).
- Class-backed helpers (not components) go in `app/Support/Tenant/`:
  - **`FieldName`**
    - `html('translations.en.name')` → `translations[en][name]`
    - `dot('translations[en][name]')` → `translations.en.name`
    - `id(string $dot, ?string $suffix=null)` → `f-translations-en-name`
  - **`Options`** — `Options::normalize(iterable|Collection|Enum-class-string $options, ?string $valueKey=null, ?string $labelKey=null): array<array{value,label,disabled?,group?,level?,image?}>`. It accepts `[value=>label]`, lists of arrays/models, backed-enum class names (uses `label()` if defined) and nested trees (`children` → `level`).
  - **`Metric`** — port `presentMetricCards()` / `formatMetricValue()` from `App\Livewire\Tenant\Base\TenantPage` **verbatim** (currency `$` + 2dp, percent, number, suffix). Pages use `Metric::cards([...])`.
  - **`TableColumn`** — fluent value object (section 4).
  - **`Select2Response`** — `Select2Response::paginate(LengthAwarePaginator $p, Closure $map): JsonResponse` → `{results:[{id,text,image?,disabled?}], pagination:{more:bool}}`.
- Every form field component shares these props:
  - `name` (dot or bracket form accepted; normalised with `FieldName`)
  - `label`, `value`, `required` (visual asterisk + `required` attribute), `disabled`, `readonly`
  - `help` (hint text under the field), `id` (default `FieldName::id()`), `wrapper-class`, `inline` (label left)
  - `:error` (optional server-side error override)
  - `$attributes` passthrough onto the actual control
- **Value resolution:** `old($dot, $value)`, so a non-AJAX fallback post-back still repopulates.
- Every field renders through `x-tenant::field`, which emits:
  ```html
  <div class="t-field {{ inline ? 't-field--inline' : '' }}" data-field="{{ dot }}">
    <label for="{{ id }}" class="field-label">Label <span class="t-req">*</span></label>
    {{ $slot }}
    <p class="t-help">help</p>
    <p class="field-error" data-error-for="{{ dot }}" role="alert" hidden></p>
  </div>
  ```
  The form engine (prompt 01 §6.4) relies on `data-field` and `data-error-for`. **Never rename them.**
- Visual language: reuse the existing classes from `app.css`: `.field-label`, `.field-control`, `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-sm`, `.card`, `.panel-title`, `.panel-copy`, `.badge`, `.badge-{cyan|green|amber|red|violet}`, `.tw`/`.tb` tables, `.toggle-field`, `.page-head`, `.page-title`, `.page-badge`, `.page-copy`, `.g-stats3`/`.g-stats4`, `.stat-head`, `.stat-value`, `.eyebrow`, `.mini-stat-dot`, `.dot-*`, `.card-glow-*`, `.empty-state*`, `.filters-*`, `.table-*-shell`, `.pagination-*`. **The look must stay identical to today.** New CSS only styles things that didn't exist before (select2, DataTables chrome, toastr, flatpickr, modal shell, dropzone previews). It uses the tokens listed in prompt 01 §4, in `resources/css/tenant/components/<name>.css`, imported from `resources/css/tenant/app.css`.
- Every interactive component has a JS module in `resources/js/tenant/components/<name>.js` exporting `init(el)` (and optionally `destroy(el)`, `beforeSubmit(el)`). It is registered in `core/index.js` via `registerComponent(selector, () => import('../components/<name>.js'))`, so it is **lazy loaded only when present on the page**.
- RTL: components read `document.documentElement.dir`. Select2 uses `dir`, flatpickr uses the locale position, and dropdowns and the modal close button mirror.
- Accessibility: labels bound via `for`, `aria-invalid` toggled by the form engine, `aria-describedby` pointing to help/error, modals `role="dialog" aria-modal="true" aria-labelledby`, buttons have `type`, focus-visible rings via `--cyan`.

---

## 1. Form building blocks

| Component | Props (beyond the shared set) | Behaviour / JS |
|---|---|---|
| `form` | `action`, `method` (GET/POST/PUT/PATCH/DELETE → hidden `_method` + `data-method`), `validate` (URL), `success` (behaviours string, prompt 01 §6.4), `confirm`, `files` (bool → multipart), `id` | Renders `<form data-tenant-form novalidate>` + `@csrf` + `<div class="t-form-errors" data-form-errors hidden></div>` + slot. |
| `field` | `name`, `label`, `help`, `required`, `inline`, `id` | Wrapper only (see §0). Used directly for custom controls. |
| `input` | `type` (text, email, number, password, url, tel, search, hidden), `placeholder`, `prefix`, `suffix`, `icon` (raw SVG slot `icon`), `min`, `max`, `step`, `maxlength`, `autocomplete`, `toggle` (password eye), `slug-from` (source field dot-name), `counter` (bool) | `password-toggle.js`, `slug.js` (slugifies from the source until the user edits the slug manually, then stops; Arabic-safe transliteration fallback: keep unicode letters, replace spaces with `-`), `char-counter.js`. `type=hidden` renders without the field wrapper. |
| `textarea` | `rows`, `maxlength`, `counter`, `autosize` | `char-counter.js`, `autosize.js`. |
| `select` | `options`, `placeholder`, `multiple`, `value` | Native `<select class="field-control">` with the placeholder option `value=""`. No JS. |
| `select2` | `options`, `value` (scalar/array), `multiple`, `placeholder`, `allow-clear` (default true when not required), `tags`, `ajax-url`, `min-input` (default 0; 2 for AJAX), `selected` (`[id=>text]` for preselected AJAX values), `tree` (indent by `level`), `template` (`default`/`image`/`flag`), `max-selection`, `close-on-select` | `select2.js` loads `vendor/select2.js`, then init with `width:'100%'`, `dir`, **`minimumResultsForSearch: 0` (search input always shown)**, `dropdownParent` = closest `.t-modal` or `document.body`, `language` (noResults/searching/inputTooShort from `data-i18n`). AJAX `delay: 250`, `data: p => ({ q: p.term || '', page: p.page || 1, ...dependsOn })`, `processResults` expects the `Select2Response` shape. `data-depends-on="country_id"` refreshes/clears when another field changes (used for country→city). Fires native `change` so validation runs. Tree mode indents via `templateResult` using `data-level`. `destroy` on modal close. Theme CSS covers selection, choices, dropdown, highlighted, search field, clear icon and `.is-invalid` border — dark and light. |
| `checkbox` | `label` (or slot), `description`, `checked`, `value` (default 1), `in-group` | Renders a hidden `name=0` input first (unless `in-group`), then the checkbox. Uses the existing `.toggle-field` look. |
| `checkbox-group` | `options`, `value` (array), `columns` (1–4), `select-all` (bool) | Name is `name[]`, `data-field="name"`; the error key is the base name. `select-all` JS toggles all. |
| `radio` | `value`, `checked`, `label`, `description` | Single radio (for custom layouts). |
| `radio-group` | `options`, `value`, `variant` (`default`/`cards`/`pills`), `columns` | `cards` renders selectable cards (label + description + optional icon/image) — used for gateways, theme variants, coupon type. |
| `switch` | `checked`, `label`, `description`, `action-url`, `action-method` (default PATCH), `payload-key` (default `active`), `confirm` | As a **form field**: hidden 0 + checkbox styled as a toggle. As a **row action** (`action-url` set, no `name`): on change → `http` with `{[payload-key]: checked?1:0}`, reverts on failure; the server toast comes via the interceptor. `switch.js`. |
| `editor` | `height` (default 400), `toolbar` (`full`/`basic`), `placeholder`, `dir` | Textarea `data-tenant-editor`. `editor.js` loads `vendor/tinymce.js` (self-hosted, prompt 01 package). The config **ports the current `x-editor` options exactly**: `menubar: 'file edit view insert format tools table'`, `plugins: 'lists link image media table wordcount code fullscreen'`, the same toolbar, `branding:false`, `promotion:false`. Add `license_key:'gpl'`, `skin` = `oxide-dark`/`oxide` and `content_css` = `dark`/`default` from the current theme (re-init on `tenant:theme-changed`), `directionality` from `dir`, `setup` → on `change input` call `editor.save()` and dispatch `input` on the textarea (so blur-validation sees it). `beforeSubmit` → `tinymce.triggerSave()`. `destroy` → `tinymce.remove()`. |
| `file` | `accept`, `current` (URL + name), `removable` (renders `remove_<name>` checkbox), `preview` (bool for images) | Styled single input; shows the chosen file name and an image preview. `file.js`. |
| `image-upload` | `current`, `removable`, `expected-width`, `expected-height`, `dimension-label`, `aspect` (css ratio), `max-kb` | Preview tile with replace/remove. Client-side dimension check shows the same warning text as the current `x-dropzone` (port `readImageDimensions`/`checkImageDimensionWarning` from `resources/js/app.js`). It is a warning, not a block. The server remains the source of truth. `image-upload.js`. |
| `dropzone` | `multiple` (default true), `accept`, `max-files`, `max-kb`, `existing` (array `{id,url,type,name}`), `remove-name` (default `remove_<name>_ids`), `order-name` (default `<name>_order`), `sortable` (bool), `expected-width`, `expected-height`, `label`, `sublabel` | Port the visual markup of the global `x-dropzone` into the tenant namespace. JS keeps a `DataTransfer` so files picked in several rounds accumulate. It renders previews (image/video/pdf icon), lets you remove new files (rebuilds `DataTransfer`) and existing ones (appends hidden `remove_*_ids[]`), and drag-sorts via SortableJS (lazy), writing `order-name` as comma-separated existing ids. It enforces `max-files`/`max-kb` client-side with an inline error. `dropzone.js`. |
| `date` | `min`, `max`, `enable-time`, `format` (default `Y-m-d` / `Y-m-d H:i`), `alt-format` (display, default `M d, Y`) | flatpickr (lazy, self-hosted) with `altInput` and the theme applied via CSS. |
| `daterange` | `start-name`, `end-name`, `start-value`, `end-value`, `enable-time` | One visible input and two hidden inputs. Errors map to both keys (`data-error-for` on each hidden field inside the wrapper). |
| `time` | — | flatpickr `noCalendar`. |
| `color` | `allow-transparent`, `swatches` (array) | Native colour input + hex text input, kept in sync both ways; "transparent" toggle when allowed. Used by the appearance/logo builder. `color.js`. |
| `phone` | `initial-country` (default from tenant country or `sa`), `preferred` (array) | intl-tel-input (already a dependency). Import `intl-tel-input` + `intl-tel-input/utils` + styles directly. **Do not import `resources/js/phone-input.js`** (it has Livewire sync code). Options are ported from that file. A hidden input holds E.164. `beforeSubmit` writes the number and `validate` flags an invalid number client-side. `phone.js`. |
| `repeater` | `name` (array base), `min`, `max`, `sortable`, `add-label`, `items` (initial rows) | Slot contains `<template data-repeater-template>` using `__INDEX__`. JS adds/removes rows, reindexes names/ids/`data-field`/`data-error-for` (`variants.__INDEX__.price` → `variants.3.price`), calls `initComponents(row)` for nested select2/files, and emits `change`. Used by product variants, option pairs, return-policy conditions and footer/social lists. `repeater.js`. |
| `locale-tabs` + `locale-pane` | tabs: `languages` (collection/array with `code`, `name`, `is_default`, `direction`), `active`; pane: `code`, `dir` | Header tabs built from `languages`. Each pane has `dir`/`lang` attributes. A tab shows a red dot when a field inside its pane is invalid (it observes `.is-invalid`). It listens to `tenant:reveal` to activate the pane containing the target field. `locale-tabs.js`. Replaces every Livewire `setActiveLocale` / `setActiveLocaleTab`. |
| `submit` | `variant`, `icon`, `loading-text` (default "Saving…") | `<button type="submit" class="btn btn-primary">` with a spinner slot. The form engine toggles `.is-loading`. |

## 2. Layout & display components

| Component | Props | Notes |
|---|---|---|
| `page-header` | `title`, `badge`, `description`, `back` (url), slot `actions`, slot `meta` | Same markup as the current `.page-head` block in `livewire/tenant/pages/list-page.blade.php`. |
| `card` | `title`, `subtitle`, `glow`, `padding` (`none`/`sm`/`md`), slots `actions`, `footer` | Same markup as the global `x-card`. |
| `card-collapse` | `title`, `subtitle`, `open` | `<details class="card collapse-card">` like the global component. |
| `stat-card` | `label`, `value`, `caption`, `dot`, `glow`, `href`, `icon`, `trend` | Markup identical to the stat card in `list-page.blade.php`. |
| `stats-grid` | `stats` (from `Metric::cards()`), `columns` (3/4, auto from count) | Loops `stat-card` with the fade-in delay classes `fu d{n}`. |
| `filters-card` | `target` (datatable id **or** ajax-list id), `title` (default "Filters"), `description`, `open` (default false), `sync-url` (default true), `export-link` (selector of the export `<a>`), slot | See §3. |
| `datatable` | see §4 | Server-side Yajra table. |
| `table` | `headers`, `striped`, `compact`, slot | Static `.tw > table.tb` for **breakdowns only** (order items, totals, price breakdowns). |
| `pagination` | `paginator`, `mode` (`link`/`ajax`), `target` (container id for ajax) | See §5. |
| `ajax-list` | `id`, `url`, slot (initial HTML), `empty` | Container that swaps its content from `{html, pagination}` JSON. It reloads on `tenant:table:reload` with its id and on filters-card changes. Used for notifications, card grids and chat history paging. `ajax-list.js`. |
| `modal` | `id`, `title`, `size` (`sm`/`md`/`lg`/`xl`/`2xl`/`full`), `static`, `description`, slots `footer`, `header-actions` | See prompt 01 §6.6. The max-width map matches the global `x-modal` (`sm:max-w-*`). Mobile: full-width bottom sheet under 640px. `modal.css`. |
| `tabs` + `tab-panel` | tabs: `tabs` (`[key=>label]` or `[key=>['label','badge','icon','href']]`), `active`, `mode` (`hash`/`link`/`local`); panel: `key` | `hash` syncs `#tab-<key>` (back button works). `link` renders `<a href>` for server-routed tabs (onboarding `tour|setup`, customer detail tabs). Listens to `tenant:reveal`. `tabs.js`. |
| `btn` | `variant` (`primary`/`secondary`/`danger`/`ghost`/`link`), `size` (`sm`/`md`), `href`, `icon` (slot), `type`, `loading` | Renders `<a>` when `href`, else `<button>`. Passes through `data-action-*`, `data-confirm`, `data-modal-open`, `data-modal-fill-url`. |
| `dropdown` + `dropdown-item` | trigger label/icon, `align` (`start`/`end`); item: `href`, `icon`, `danger`, action attributes | Row-actions menu (kebab). Keyboard: Enter/Space opens, arrows move, Esc closes. Closes on outside click and on DataTables redraw. Auto-flips when near the viewport edge. `dropdown.js`. |
| `badge` | `color` (`cyan`/`green`/`amber`/`red`/`violet`/`gray`), `dot` | Existing `.badge .badge-*`. Add `.badge-gray` in tenant CSS using `--t3`/`--border2`. |
| `status-badge` | `status` (backed enum, string or bool), `map` (optional) | Color resolution: the enum `color()` method if present, else `config('tenant-ui.status_colors')[<enum-class-basename>][value]`, else the generic map (`active/paid/approved/completed/delivered/success` → green, `pending/processing/in_progress/review` → amber, `rejected/failed/cancelled/expired/inactive` → red, `refunded/returned` → violet, default cyan). Label = enum `label()` or headline(value). Create `config/tenant-ui.php`. |
| `empty-state` | `title`, `copy`, `icon`, slot `action` | Existing `.empty-state` markup. |
| `avatar` | `name`, `seed`, `size` (26/30/40/64), `src` | **Local initials avatar** (1–2 letters) on a deterministic gradient picked from the token palette by `crc32(seed)`. **Replaces every `api.dicebear.com` URL.** |
| `alert` | `type` (`info`/`success`/`warning`/`danger`), `title`, `dismissible` | Token-coloured callout. |
| `progress` | `value`, `max`, `color`, `label`, `show-value` | Width set via a CSS custom property `style="--p:42%"` (allowed dynamic value). |
| `kv` + `kv-row` | kv: `columns` (1/2); row: `label`, slot | Description list for detail pages (order, return, billing, customer). |
| `chat` | `messages` (array of `{id, author, role, is_me, body, attachments:[{name,url}], at}`), `composer` (slot), `empty`, `id` | Thread UI for support tickets, manufacturing, brand requests, product requests and return notes. Auto-scrolls to the bottom and exposes `appendMessage(el, message)` for Echo. Body is escaped; newlines become `<br>` via `nl2br(e())`. `chat.js`. |
| `sortable-list` | `id`, `save-url`, `method` (default POST), `payload-key` (default `ids`), `autosave` (default true), `handle` (default `.t-drag`), slot of items with `data-id` | SortableJS (lazy). On drop it POSTs `{ids:[…]}` (or shows a "Save order" button when `autosave=false`). Toast comes from the response. It reverts the DOM order if the request fails. Used by sort products, sort categories, category products, badge products, banners and page builder sections. `sortable-list.js`. |
| `chart` | `id`, `type`, `config` (array → JSON island), `height` | chart.js (lazy). Colours resolved from CSS vars at render time. Rebuilds on `tenant:theme-changed`. Port `tooltipOptions`, `gridColor`, `chartColor`, `formatChartValue`, `buildLineDatasets`, `buildBarDatasets` and `buildRadarDatasets` from `resources/js/app.js` into `resources/js/tenant/components/chart.js`, generalised to read the dataset config from the island instead of hard-coded canvas ids. `chart.js`. |
| `json-tree` | `data`, `open-depth` | Port of the global `x-json-tree` (used in order details), with its CSS/JS moved into tenant files. `json-tree.js` if it has toggles. |
| `copy` | `value`, `label` | Copies to the clipboard and toasts "Copied". Used for DNS records, webhook URLs and API keys. `copy.js`. |
| `icon` | `name`, `size` | Port `resources/views/layouts/tenant/icon.blade.php` (it currently includes `layouts.app.icon`). The tenant copy lives at `tenant/components/icon.blade.php` so the panel doesn't depend on the central admin icon file. Copy the SVG map. |
| `skeleton` | `lines`, `height` | Loading placeholder used by the datatable processing overlay and ajax-list. |

## 3. `filters-card` contract
```blade
<x-tenant::filters-card target="orders-table" export-link="#orders-export">
    <x-tenant::input name="search" label="Search" placeholder="Order UUID or customer" :value="request('search')" />
    <x-tenant::select2 name="status" label="Status" :options="$statusOptions" :value="request('status')" placeholder="All" />
</x-tenant::filters-card>
```
- Markup: port the existing `<details class="card filters-card">` block from `list-page.blade.php`. It shows an **active-filter count pill** instead of the static "Tenant" pill, and has a **Reset** button.
- `filters-card.js`:
  - Collects every named control inside the card into a plain object (multi-select → array).
  - Debounces text input by 350 ms; selects and dates apply immediately.
  - Stores the object on the target (`target._filters`) and emits `tenant:table:reload` for the target.
  - Updates the pill count.
  - When `sync-url` is on, it `history.replaceState`s the query string (`?search=…&status=…`) so reloads and back navigation keep filters. Controllers pass `request('<key>')` as initial values, so the first server render and the first DataTables request agree.
  - Rewrites the `export-link` `href` query (`?filters[search]=…`).
  - Reset clears the controls (select2 via `val(null).trigger('change')`, flatpickr `clear()`), the URL and the table.
  - Filter controls **never** go through the form engine. They are not inside `data-tenant-form`.

## 4. `datatable` contract

### PHP value object `App\Support\Tenant\TableColumn`
```php
TableColumn::make('uuid', 'Order')                 // data key, title
    ->name('orders.uuid')                          // server column name for ordering (optional)
    ->orderable(false)->searchable(false)          // defaults: orderable true, searchable false
    ->className('text-end')->width('140px')
    ->priority(1)                                  // responsive priority (1 = never hidden)
    ->visible(true);
TableColumn::index();                              // DT_RowIndex "#", not orderable
TableColumn::actions('Actions');                   // data 'actions', not orderable, priority 1, className 't-actions'
TableColumn::select();                             // checkbox column for bulk actions
->toArray()  // ['data','name','title','orderable','searchable','className','width','responsivePriority','visible']
```

### Blade
```blade
<x-tenant::datatable
    id="orders-table"
    :url="route('tenant.orders.data')"
    :columns="$columns"
    :order="[[6, 'desc']]"
    :page-length="10"
    title="Order Queue"
    description="Orders matching the current filters."
    empty-title="No orders found"
    empty-copy="No tenant orders match the current filters yet."
    quick-search            {{-- renders a header search box bound to filters[search] of the linked filters-card --}}
    filters="orders-filters" {{-- optional explicit filters-card id; defaults to the one whose target = this id --}}
    selectable              {{-- optional bulk selection --}}
    bulk-url="…" bulk-method="DELETE" bulk-confirm="Delete selected?"
>
    <x-slot:toolbar>
        <x-tenant::btn variant="secondary" size="sm" id="orders-export" :href="route('tenant.orders.export')">Export</x-tenant::btn>
    </x-slot:toolbar>
</x-tenant::datatable>
```

### Rendering
- Card shell identical to the current `.table-card-shell`: header (title, dynamic description, quick-search, toolbar), then the `<table id data-tenant-datatable data-config='@json($config)' class="tb">` inside `.tw`, with `<thead>` rendered from the column titles for no-JS readability.
- Footer uses the same `.table-footer-shell` / `.pagination-shell` look. Use DataTables 2 `layout`:
  ```js
  layout: { topStart: null, topEnd: null, bottomStart: 'info', bottomEnd: 'paging', bottom2Start: 'pageLength' }
  ```
  CSS makes DT's paging buttons look exactly like `x-tenant::pagination` chips.

### `datatable.js`
- `$.fn.dataTable.ext.errMode = 'none'`. On `dt-error`, toast "Couldn't load the table. Please retry.".
- `serverSide: true`, `processing: true` (custom skeleton overlay), `responsive: true`, `autoWidth: false`, `searching: false` (search goes through filters), `deferRender: true`.
- `ajax: { url, type: 'GET', data: d => { d.filters = el._filters || initialFiltersFromQuery(); } }`.
- `pageLength` persisted in `localStorage['dt:len:<id>']`. `lengthMenu: [10, 25, 50, 100]`.
- `language` from a `data-i18n` JSON built in the component with `__()` strings (processing, info, infoEmpty, infoFiltered, lengthMenu, paginate.*, emptyTable → the `empty-title` + `empty-copy` rendered via the `x-tenant::empty-state` markup).
- `drawCallback`:
  - `initComponents(tbody)` (row switches, dropdowns)
  - updates the header description to "`{recordsFiltered}` records matched" when `description` contains the `:count` placeholder (`description="\:count orders matched the current queue filters."`)
  - emits `tenant:table:drawn`
- Listens to `tenant:table:reload` (matching selector) → `table.ajax.reload(null, false)` (keeps the current page), or `true` to reset paging when filters change.
- `selectable`: header checkbox, row checkboxes, a bulk bar with the selected count and an action button using `core/actions.js` with the payload `{ids:[…]}`. Selection is cleared on reload.
- Exposes `el._dt`, `reload(el, resetPaging)`.

### Client mode
For small, already-loaded datasets (dashboard widgets): `<x-tenant::datatable id=… :rows="$rows" :columns="$columns" mode="client" :paging="false" :searching="false" />`. The tbody is rendered server-side (cells via the same `_cols` partials) and DataTables only enhances sorting/responsiveness. Same shell and look.

## 5. `pagination` contract
- Markup identical to the current global `x-pagination` output (read `resources/views/components/pagination.blade.php`, but **remove every `wire:` attribute**):
  - prev/next arrows
  - numbered chips with ellipsis via `$paginator->onEachSide(1)->linkCollection()`
  - "Showing x–y of z" info
  - disabled states
  - `rel="prev|next"`
- `mode="link"`: plain anchors, preserving the current query string (`$paginator->withQueryString()`).
- `mode="ajax"`: anchors carry `data-ajax-page`. `ajax-list.js` intercepts, GETs the href with `Accept: application/json`, expects `{html, pagination}` (produced by `PanelController::fragment(string $view, array $data, LengthAwarePaginator $p)` — add this helper, rendering `view()->render()` for the list and `view('tenant::components.pagination', ['paginator'=>$p,'mode'=>'ajax'])->render()` for the pager), swaps both, scrolls the container top into view, and updates `?page=` via `replaceState`.

## 6. Vendor loaders (created in prompt 01 §6, completed here)
- `vendor/select2.js`: `import $ from './jquery.js'; import factory from 'select2'; import 'select2/dist/css/select2.css'; if (typeof factory === 'function' && !$.fn.select2) factory(window, $); export default $;`. Assert `$.fn.select2` exists.
- `vendor/datatables.js`: `import './jquery.js'; import DataTable from 'datatables.net-dt'; import 'datatables.net-responsive-dt'; import 'datatables.net-dt/css/dataTables.dataTables.css'; import 'datatables.net-responsive-dt/css/responsive.dataTables.css'; export default DataTable;`.
- `vendor/flatpickr.js`: flatpickr + its CSS, plus `flatpickr/dist/l10n/ar.js` registered when `lang` starts with `ar`.
- `vendor/tinymce.js`: `import tinymce from 'tinymce/tinymce'`, then `'tinymce/models/dom/model'`, `'tinymce/themes/silver'`, `'tinymce/icons/default'`, the plugins (`lists`, `link`, `image`, `media`, `table`, `wordcount`, `code`, `fullscreen`) via `tinymce/plugins/<p>`, and the bundlable skins `tinymce/skins/ui/oxide/skin.js`, `tinymce/skins/ui/oxide-dark/skin.js`, `tinymce/skins/content/default/content.js` and `tinymce/skins/content/dark/content.js`. If a skin `.js` path does not exist in the installed 7.x, import the `.min.css` with `?inline` and pass it via `skin:false` + `content_style`. Verify after `npm i` with `ls node_modules/tinymce/skins/ui/oxide`. Export `tinymce`.
- The vendor loaders import their CSS so styles only ship with the widget. The overrides in `resources/css/tenant/components/*.css` load globally (small) and win by specificity (`.t-scope .select2-container …`).

## 7. UI kit page (QA aid, local only)
- `App\Http\Controllers\Tenant\Panel\UiKitController@__invoke`, route `GET /admin/_ui` named `tenant.ui-kit`, registered **only** `if (app()->environment('local'))` inside the authenticated group in `routes/tenant_panel.php`.
- View `resources/views/tenant/pages/ui-kit.blade.php` renders every component above in every state (default, disabled, error, RTL pane), a demo `form` with validate/submit endpoints (`POST /admin/_ui/validate`, `POST /admin/_ui`, backed by `UiKitRequest` with a rule per field type), a demo `datatable` fed by `DataTables::collection()` of 60 fake rows, a modal, a sortable list, a chart, a chat thread, and toasts for each type.
- JS entry `resources/js/tenant/pages/ui-kit.js`. Register it in `vite.config.js`.

## 8. Verification
1. `/admin/_ui` (local) renders with zero console errors in dark and light, LTR and an `<html dir="rtl">` toggle button on the page.
2. Blur-validation shows errors under the touched fields only. Submit shows all errors, focuses the first one and activates its locale tab. Success toasts in the themed toastr.
3. Select2: search box visible on every select2, AJAX paging works, preselected AJAX values render, and it works inside a modal (dropdownParent).
4. Datatable: server paging, ordering, filters (URL-synced), reset, page length persistence, quick search, responsive collapse at 375px, bulk select, error toast when the endpoint 500s.
5. Network tab: `vendor-jquery` / `vendor-tinymce` / `vendor-flatpickr` chunks load only on pages that contain those components. No third-party hosts.
6. `grep -rn "wire:\|x-data\|@click\|<style\|<script" resources/views/tenant/components` returns nothing, except `<script type="application/json"` islands.
