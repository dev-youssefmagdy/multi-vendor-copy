@extends('layouts.app')

{{-- Dedup plan §4.2a / §7: Blade + controller JSON + fetch() (was the Livewire ProductEditRequestsList). Approve stores
     only the changed (field, locale) pairs as tenant field overrides; reset returns them to the LIVE central value.
     Every action reloads the page on success (list, stats, sidebar counter); the controller flashes the message so the
     layout's #flash-status toast shows it after the reload. Errors are shown in place. --}}
@php
    $fieldLabels = ['name' => __('Name'), 'description' => __('Description')];
    $i18n = [
        'working' => __('Working…'),
        'loading' => __('Loading…'),
        'failed' => __('Request failed.'),
        'unexpected' => __('Unexpected server response.'),
        'sessionExpired' => __('Your session has expired. Reload the page and try again.'),
        'forbidden' => __('You do not have permission to do this.'),
        'notFound' => __('This request no longer exists. Reload the page.'),
        'noChanges' => __('No differences between current and requested values.'),
        'fieldsChanged' => __(':count field(s) changed'),
        'current' => __('Current'),
        'requested' => __('Requested'),
        'activeOverrides' => __('Active overrides for this store'),
        'centralChanged' => __('Central changed since approval'),
        'centralNow' => __('Central now'),
        'thisRequest' => __('From this request'),
        'resetField' => __('Reset field'),
        'adminNote' => __('Admin note'),
        'confirmApprove' => __('Approve this edit request? Only the changed fields will override the central product for this store.'),
        'confirmReset' => __("Reset this request's fields back to the live central values?"),
        'confirmResetField' => __('Reset :field (:locale) to the live central value?'),
        'confirmResetAll' => __('Reset ALL overrides of this product for this store to the live central values?'),
        'fields' => $fieldLabels,
    ];
@endphp

@section('content')
<main id="mn" data-edit-requests
      data-show-url="{{ route('admin.products.edit-requests.show', ['editRequest' => '__ID__']) }}"
      data-approve-url="{{ route('admin.products.edit-requests.approve', ['editRequest' => '__ID__']) }}"
      data-reject-url="{{ route('admin.products.edit-requests.reject', ['editRequest' => '__ID__']) }}"
      data-reset-url="{{ route('admin.products.edit-requests.reset', ['editRequest' => '__ID__']) }}"
      data-csrf="{{ csrf_token() }}">
    <script type="application/json" data-er-i18n>@json($i18n)</script>

    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">{{ __('Catalog') }}</div>
            <h1 class="D page-title">{{ __('Product Edit Requests') }}</h1>
            <p class="page-copy">{{ __('Review vendor requests to update product names and descriptions. Approving keeps only the changed fields for that store; every other field keeps following the central product.') }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="g-stats4 section-gap">
        @foreach ([
            [__('Total'), $stats['total'], 'cyan', __('All edit requests across all vendors.')],
            [__('Pending'), $stats['pending'], 'amber', __('Awaiting your review and decision.')],
            [__('Approved'), $stats['approved'], 'green', __('Changed fields override the central product.')],
            [__('Rejected'), $stats['rejected'], 'red', __('Requests declined by admin.')],
        ] as [$label, $value, $tone, $copy])
            <div class="card card-glow-{{ $tone }}">
                <div class="stat-head">
                    <div>
                        <div class="eyebrow">{{ $label }}</div>
                        <div class="D stat-value">{{ number_format($value) }}</div>
                    </div>
                    <div class="mini-stat-dot dot-{{ $tone }}"></div>
                </div>
                <p class="section-copy">{{ $copy }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters (plain GET form; selects submit on change, search after a short pause or Enter) --}}
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">{{ __('Filters') }}</h3>
                <p class="panel-copy">{{ __('Search by product slug or filter by status and vendor.') }}</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9" /></svg>
        </summary>
        <form method="GET" action="{{ route('admin.products.edit-requests') }}" class="filters-grid" data-autosubmit>
            <div>
                <label class="field-label" for="er-search">{{ __('Search') }}</label>
                <input id="er-search" type="search" name="search" class="field-control" placeholder="{{ __('Product slug or vendor ID…') }}" value="{{ $filters['search'] }}" autocomplete="off">
            </div>
            <div>
                <label class="field-label" for="er-status">{{ __('Status') }}</label>
                <select id="er-status" name="status" class="field-control">
                    @foreach (['pending' => __('Pending'), 'all' => __('All statuses'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="er-tenant">{{ __('Vendor') }}</label>
                <select id="er-tenant" name="tenant" class="field-control">
                    <option value="">{{ __('All vendors') }}</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected($filters['tenant'] === (string) $tenant->id)>{{ $tenant->name ?: $tenant->id }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </details>

    {{-- Table --}}
    <section class="card table-card-shell">
        @if ($records->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Locales') }}</th>
                            <th>{{ __('Changes') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Submitted') }}</th>
                            <th class="ta-r">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            @php
                                $locales = array_keys(array_merge($record->requested_translations ?? [], $record->current_translations ?? []));
                                $changedFields = $changes[$record->id] ?? [];
                            @endphp
                            <tr data-request-row="{{ $record->id }}">
                                <td>
                                    <div class="entity-title">{{ $record->product_slug ?: '#' . $record->product_id }}</div>
                                    <div class="entity-subtitle">{{ __('ID') }} {{ $record->product_id }}@if ($record->central_product_id) · {{ __('central') }} #{{ $record->central_product_id }}@endif</div>
                                </td>
                                <td>
                                    <div class="entity-title">{{ data_get($record->tenant?->data, 'name', $record->tenant_id) }}</div>
                                    <div class="entity-subtitle">{{ $record->tenant_id }}</div>
                                </td>
                                <td><div class="entity-subtitle">{{ implode(', ', array_map('strtoupper', $locales)) }}</div></td>
                                <td>
                                    @forelse ($changedFields as $locale => $fields)
                                        <div class="entity-subtitle">[{{ strtoupper($locale) }}] {{ implode(', ', array_map(fn ($f) => $fieldLabels[$f] ?? ucfirst($f), array_keys($fields))) }}</div>
                                    @empty
                                        <span class="entity-subtitle">{{ __('No changes') }}</span>
                                    @endforelse
                                </td>
                                <td><span class="badge {{ $record->status->badgeClass() }}">{{ __($record->status->label()) }}</span></td>
                                <td><div class="entity-subtitle" title="{{ $record->created_at }}">{{ $record->created_at->diffForHumans() }}</div></td>
                                <td class="ta-r" style="min-width:160px">
                                    <div class="er-actions">
                                        <button type="button" class="btn btn-secondary btn-sm" data-er-details="{{ $record->id }}">{{ __('View changes') }}</button>
                                        @if ($record->status->value === 'pending')
                                            <button type="button" class="btn btn-primary btn-sm" data-er-approve="{{ $record->id }}">{{ __('Approve') }}</button>
                                            <button type="button" class="btn btn-secondary btn-sm btn-danger" data-er-reject="{{ $record->id }}">{{ __('Reject') }}</button>
                                        @elseif ($record->status->value === 'approved')
                                            <button type="button" class="btn btn-secondary btn-sm" data-er-reset="{{ $record->id }}">{{ __('Reset to Central') }}</button>
                                        @elseif ($record->admin_note)
                                            <div class="entity-subtitle" title="{{ $record->admin_note }}">{{ Str::limit($record->admin_note, 40) }}</div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-pagination"><x-link-pagination :paginator="$records" /></div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">{{ __('No requests found') }}</h3>
                <p class="panel-copy">{{ __('No product edit requests match the current filters.') }}</p>
            </div>
        @endif
    </section>

    {{-- Details modal (filled by JS) --}}
    <div class="ier-backdrop" data-er-modal="details" hidden>
        <div class="ier-modal" style="max-width:960px" role="dialog" aria-modal="true" aria-labelledby="er-details-title">
            <div class="ier-head">
                <div>
                    <h3 class="panel-title" id="er-details-title">{{ __('Requested Changes') }}</h3>
                    <p class="panel-copy" style="margin-top:2px" data-er-subtitle></p>
                </div>
                <button type="button" class="ier-close" data-er-close aria-label="{{ __('Close') }}">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="ier-body" data-er-body></div>
            <p class="field-error ier-inline-error" data-er-details-error hidden></p>
            <div class="ier-foot">
                <button type="button" class="btn btn-secondary btn-danger" data-er-reset-all hidden>{{ __('Reset all overrides of this product') }}</button>
                <button type="button" class="btn btn-secondary" data-er-close>{{ __('Close') }}</button>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div class="ier-backdrop" data-er-modal="reject" hidden>
        <div class="ier-modal" role="dialog" aria-modal="true" aria-labelledby="er-reject-title">
            <div class="ier-head">
                <h3 class="panel-title" id="er-reject-title">{{ __('Reject Edit Request') }}</h3>
                <button type="button" class="ier-close" data-er-close aria-label="{{ __('Close') }}">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="ier-body">
                <label class="field-label" for="er-note">{{ __('Admin Note') }} <span class="field-optional">{{ __('(optional — visible to vendor)') }}</span></label>
                <textarea id="er-note" class="field-control" rows="3" maxlength="500" placeholder="{{ __('Reason for rejection…') }}" data-er-note></textarea>
                <p class="field-error" data-er-note-error hidden></p>
            </div>
            <div class="ier-foot">
                <button type="button" class="btn btn-secondary" data-er-close>{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary btn-danger" data-er-confirm-reject>{{ __('Reject Request') }}</button>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
    .er-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
    .ier-backdrop { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(3, 7, 15, .66); backdrop-filter: blur(3px); }
    .ier-backdrop[hidden] { display: none; }
    .ier-modal { width: 100%; max-width: 520px; max-height: calc(100vh - 48px); display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--border2); border-radius: 16px; box-shadow: 0 24px 64px rgba(0, 0, 0, .45); overflow: hidden; }
    .ier-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 18px 22px; border-bottom: 1px solid var(--border); }
    .ier-body { padding: 20px 22px; overflow-y: auto; flex: 1; }
    .ier-inline-error { margin: 0; padding: 0 22px 12px; }
    .ier-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 22px; border-top: 1px solid var(--border); background: var(--surface); }
    .ier-close { flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--t3); cursor: pointer; }
    .ier-close:hover { color: var(--t1); border-color: var(--border2); }
    .ier-locale + .ier-locale, .ier-overrides, .ier-note { margin-top: 22px; }
    .ier-locale-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .ier-field + .ier-field { margin-top: 14px; }
    .ier-field-label { font-size: 12px; font-weight: 600; color: var(--t2); margin-bottom: 6px; }
    .ier-diff { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ier-pane { border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; min-width: 0; }
    .ier-old { background: rgba(255, 77, 109, .07); border-color: rgba(255, 77, 109, .28); }
    .ier-new { background: rgba(0, 214, 143, .07); border-color: rgba(0, 214, 143, .3); }
    .ier-pane-label { font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--t3); margin-bottom: 4px; }
    .ier-text { font-size: 13px; line-height: 1.55; color: var(--t1); white-space: pre-wrap; overflow-wrap: anywhere; max-height: 260px; overflow-y: auto; unicode-bidi: plaintext; text-align: start; }
    .ier-override { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 1px solid var(--border); }
    .ier-override .badge { margin-top: 6px; margin-inline-end: 6px; }
    .ier-override .entity-subtitle { unicode-bidi: plaintext; }
    [data-edit-requests] button[aria-busy="true"] { opacity: .65; cursor: progress; }
    @media (max-width: 720px) { .ier-diff { grid-template-columns: 1fr; } .ier-backdrop { padding: 12px; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-edit-requests]');
    if (!root) return;

    const T = JSON.parse(root.querySelector('[data-er-i18n]').textContent || '{}');
    const fmt = (text, params = {}) => Object.entries(params).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), String(text ?? ''));
    const fieldLabel = (field) => (T.fields || {})[field] || (field.charAt(0).toUpperCase() + field.slice(1));
    const url = (key, id) => root.dataset[key].replace('__ID__', encodeURIComponent(id));
    const modals = { details: root.querySelector('[data-er-modal="details"]'), reject: root.querySelector('[data-er-modal="reject"]') };
    const detailsError = root.querySelector('[data-er-details-error]');
    let rejectingId = null;
    let viewingId = null;
    let lastFocus = null;

    // Admin toast (resources/js/app.js listens for the "admin-toast" event on the global Livewire bus).
    const toast = (message, type = 'success') => {
        if (window.Livewire?.dispatch) { window.Livewire.dispatch('admin-toast', { message, type }); } else { window.alert(message); }
    };
    // DOMParser builds an inert document: no scripts or event handlers run (vendor text is untrusted, QA D-P2-1).
    const strip = (html) => (new DOMParser().parseFromString(String(html ?? ''), 'text/html').body.textContent || '').trim() || '—';
    const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; };

    // Loading state: disable + aria-busy + "Working…" label, restored afterwards.
    const busy = (button, on, label = T.working) => {
        if (!button) return;
        if (on) {
            button.dataset.label = button.textContent;
            button.textContent = label;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        } else {
            if (button.dataset.label !== undefined) button.textContent = button.dataset.label;
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    };

    async function call(method, target, body) {
        let res;
        try {
            res = await fetch(target, {
                method,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: body ? JSON.stringify(body) : undefined,
            });
        } catch (e) {
            throw new Error(T.failed);
        }
        const data = await res.json().catch(() => null);
        if (res.status === 419) throw new Error(T.sessionExpired);
        if (res.status === 403) throw new Error(data?.message || T.forbidden);
        if (res.status === 404) throw new Error(T.notFound);
        if (!data) throw new Error(T.unexpected);
        if (!res.ok || data.success === false) {
            const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            throw new Error(first || data.message || T.failed);
        }
        return data;
    }

    const open = (name) => {
        lastFocus = document.activeElement;
        modals[name].hidden = false;
        (modals[name].querySelector('textarea') || modals[name].querySelector('[data-er-close]'))?.focus();
    };
    const close = () => {
        Object.values(modals).forEach((m) => { m.hidden = true; });
        rejectingId = null;
        viewingId = null;
        detailsError.hidden = true;
        lastFocus?.focus?.();
    };

    // Success → reload (the controller flashed the message; the layout toasts it after the reload).
    async function runAction(button, promise, onError = (msg) => toast(msg, 'error')) {
        busy(button, true);
        try {
            await promise();
            window.location.reload();
        } catch (e) {
            busy(button, false);
            onError(e.message);
        }
    }

    function renderDetails(data) {
        const body = root.querySelector('[data-er-body]');
        body.replaceChildren();
        detailsError.hidden = true;
        root.querySelector('[data-er-subtitle]').textContent = `${data.request.product} · ${data.request.vendor} · ${data.request.status_label || data.request.status}`;

        const locales = Object.entries(data.changes || {});
        if (!locales.length) body.append(el('p', 'panel-copy', T.noChanges));
        locales.forEach(([locale, fields]) => {
            const section = el('section', 'ier-locale');
            const head = el('div', 'ier-locale-head');
            head.append(el('span', 'badge', locale.toUpperCase()), el('span', 'entity-subtitle', fmt(T.fieldsChanged, { count: Object.keys(fields).length })));
            section.append(head);
            Object.entries(fields).forEach(([field, values]) => {
                const wrap = el('div', 'ier-field');
                wrap.append(el('div', 'ier-field-label', fieldLabel(field)));
                const diff = el('div', 'ier-diff');
                [['ier-old', T.current, values.current], ['ier-new', T.requested, values.requested]].forEach(([cls, label, text]) => {
                    const pane = el('div', `ier-pane ${cls}`);
                    const content = el('div', 'ier-text', strip(text));
                    content.dir = 'auto';
                    pane.append(el('div', 'ier-pane-label', label), content);
                    diff.append(pane);
                });
                wrap.append(diff);
                section.append(wrap);
            });
            body.append(section);
        });

        if (data.request.admin_note) {
            const note = el('section', 'ier-note');
            note.append(el('div', 'ier-field-label', T.adminNote), el('div', 'ier-text', data.request.admin_note));
            body.append(note);
        }

        const overrides = data.overrides || [];
        root.querySelector('[data-er-reset-all]').hidden = overrides.length === 0;
        if (overrides.length) {
            const box = el('section', 'ier-overrides');
            box.append(el('h4', 'panel-title', T.activeOverrides));
            overrides.forEach((o) => {
                const localeLabel = String(o.locale || '').toUpperCase();
                const row = el('div', 'ier-override');
                const info = el('div');
                info.append(el('div', 'entity-title', `${localeLabel ? `[${localeLabel}] ` : ''}${fieldLabel(o.field)}`));
                const value = el('div', 'entity-subtitle', strip(o.value));
                value.dir = 'auto';
                info.append(value);
                if (o.this_request) info.append(el('span', 'badge badge-cyan', T.thisRequest));
                if (o.central_changed) {
                    info.append(el('span', 'badge badge-amber', T.centralChanged));
                    const central = el('div', 'entity-subtitle', `${T.centralNow}: ${strip(o.central_value)}`);
                    central.dir = 'auto';
                    info.append(central);
                }
                const btn = el('button', 'btn btn-secondary btn-sm', T.resetField);
                btn.type = 'button';
                btn.addEventListener('click', () => {
                    if (!window.confirm(fmt(T.confirmResetField, { field: fieldLabel(o.field), locale: localeLabel || '—' }))) return;
                    runAction(btn, () => call('POST', url('resetUrl', viewingId), { scope: 'field', field: o.field, locale: o.locale }), showDetailsError);
                });
                row.append(info, btn);
                box.append(row);
            });
            body.append(box);
        }
    }

    const showDetailsError = (message) => { detailsError.textContent = message; detailsError.hidden = false; };

    root.addEventListener('click', async (event) => {
        const t = event.target.closest('button, [data-er-modal]');
        if (!t) return;

        if (t.matches('[data-er-modal]') && event.target === t) { close(); return; }
        if (t.matches('[data-er-close]')) { close(); return; }
        if (t.disabled) return;

        if (t.dataset.erDetails) {
            busy(t, true, T.loading);
            try {
                const data = await call('GET', url('showUrl', t.dataset.erDetails));
                busy(t, false);
                viewingId = t.dataset.erDetails;
                renderDetails(data);
                open('details');
            } catch (e) {
                busy(t, false);
                toast(e.message, 'error');
            }
            return;
        }
        if (t.dataset.erApprove) {
            if (!window.confirm(T.confirmApprove)) return;
            runAction(t, () => call('POST', url('approveUrl', t.dataset.erApprove)));
            return;
        }
        if (t.dataset.erReject) {
            rejectingId = t.dataset.erReject;
            root.querySelector('[data-er-note]').value = '';
            root.querySelector('[data-er-note-error]').hidden = true;
            open('reject');
            return;
        }
        if (t.matches('[data-er-confirm-reject]') && rejectingId) {
            const note = root.querySelector('[data-er-note]').value;
            const err = root.querySelector('[data-er-note-error]');
            err.hidden = true;
            runAction(t, () => call('POST', url('rejectUrl', rejectingId), { admin_note: note }), (message) => { err.textContent = message; err.hidden = false; });
            return;
        }
        if (t.dataset.erReset) {
            if (!window.confirm(T.confirmReset)) return;
            runAction(t, () => call('POST', url('resetUrl', t.dataset.erReset), { scope: 'request' }));
            return;
        }
        if (t.matches('[data-er-reset-all]') && viewingId) {
            if (!window.confirm(T.confirmResetAll)) return;
            runAction(t, () => call('POST', url('resetUrl', viewingId), { scope: 'all' }), showDetailsError);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && Object.values(modals).some((m) => !m.hidden)) close();
    });

    const filters = root.querySelector('[data-autosubmit]');
    if (filters) {
        let timer;
        const FOCUS_KEY = 'admin-edit-requests-search-focus';
        const submit = () => { clearTimeout(timer); filters.requestSubmit ? filters.requestSubmit() : filters.submit(); };
        filters.querySelectorAll('select').forEach((s) => s.addEventListener('change', submit));
        const search = filters.querySelector('input[name="search"]');
        const keepFocus = () => { try { sessionStorage.setItem(FOCUS_KEY, '1'); } catch (e) {} };
        search?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => { keepFocus(); submit(); }, 600); });
        search?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); keepFocus(); submit(); } });
        // Typing reloads the page (GET form): put the caret back so the admin can keep typing, like the old live search.
        try {
            if (search && sessionStorage.getItem(FOCUS_KEY) === '1') {
                sessionStorage.removeItem(FOCUS_KEY);
                search.focus();
                search.setSelectionRange(search.value.length, search.value.length);
            }
        } catch (e) {}
    }
})();
</script>
@endpush
