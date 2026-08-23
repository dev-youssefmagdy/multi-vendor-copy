@extends('layouts.app')

@section('content')
@php
    $isEdit = $tenant !== null;
    $formAction = $isEdit
        ? route('admin.plans.users.update', $tenant)
        : route('admin.plans.users.store');
    $f = fn(string $key, mixed $fallback = '') => old($key, $tenant->{$key} ?? $fallback);
    $currentDomain = $tenant?->domains->first()?->domain;
    $currentCategoryIds = old('categoryIds', $categoryIds ?? []);
@endphp

<main id="mn">
    <div x-data="{ tab: '{{ old('_tab', 'account') }}' }" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Tenants</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage tenant identity, onboarding status, package assignment, and primary domain.
                </p>
            </div>
            <div class="page-actions" style="flex-shrink:0">
                <a href="{{ route('admin.plans.users') }}" class="btn btn-secondary">Back to Tenants</a>
                @if ($isEdit)
                    <a href="{{ route('admin.plans.users.impersonate', $tenant->id) }}" class="btn btn-secondary"
                        title="Log in to this tenant's admin panel as the store owner">
                        Login to Store
                    </a>
                @endif
                <button type="submit" form="tenant-form" class="btn btn-primary" x-show="tab === 'account'">Save Tenant</button>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card section-gap notice-error">
                <h3 class="panel-title">Please fix the highlighted fields</h3>
                <p class="panel-copy">The form was not saved because some required values are missing or invalid.</p>
            </div>
        @endif

        {{-- ── Tabs ─────────────────────────────────────────────────────────── --}}
        <div class="section-tabs" style="margin-bottom:0">
            <button type="button" class="section-tab" :class="{ 'is-active': tab === 'account' }" @click="tab = 'account'">
                <svg class="tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 20c0-3.866 3.582-7 8-7s8 3.134 8 7" />
                </svg>
                <div>
                    <span>Account</span>
                    <small>Identity &amp; settings</small>
                </div>
            </button>
            @if ($isEdit)
                <button type="button" class="section-tab" :class="{ 'is-active': tab === 'billing' }" @click="tab = 'billing'">
                    <svg class="tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                        <line x1="2" y1="10" x2="22" y2="10" />
                        <line x1="6" y1="15" x2="10" y2="15" />
                    </svg>
                    <div>
                        <span>Billing &amp; Plans</span>
                        <small>Subscriptions &amp; history</small>
                    </div>
                </button>
            @endif
        </div>

        {{-- ── Account Tab ──────────────────────────────────────────────────── --}}
        <div x-show="tab === 'account'">
            <form id="tenant-form" method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf
                @if ($isEdit) @method('PUT') @endif
                <input type="hidden" name="_tab" value="account">

                <x-card-collapse title="Tenant Details"
                    subtitle="Identity, package assignment, and primary domain configuration." class="form-card"
                    :start-open="true">
                    <div class="form-grid form-grid-2">
                        <div>
                            <label class="field-label">Shop Name</label>
                            <x-input type="text" name="name" class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                                value="{{ $f('name') }}" />
                            @error('name') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Slug</label>
                            <x-input type="text" name="slug" class="{{ $errors->has('slug') ? 'is-invalid' : '' }}"
                                value="{{ $f('slug') }}" />
                            @error('slug') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Owner Admin Email</label>
                            <x-input type="email" name="email" class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                value="{{ $f('email') }}" />
                            @error('email') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Phone</label>
                            <x-input type="tel" name="phone" data-phone-input
                                class="{{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                value="{{ old('phone', $phone ?? '') }}" />
                            @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Status</label>
                            <x-select name="status" class="{{ $errors->has('status') ? 'is-invalid' : '' }}">
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}"
                                        {{ old('status', $tenant->status->value ?? 'onboarding') === $statusOption->value ? 'selected' : '' }}>
                                        {{ ucfirst($statusOption->value) }}
                                    </option>
                                @endforeach
                            </x-select>
                            @error('status') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Categories</label>
                            <p class="field-hint" style="margin-bottom:8px;">Select the root (parent) categories this tenant
                                can access. Leave empty for all categories.</p>
                            @if ($rootCategories->isEmpty())
                                <p class="field-hint" style="color:var(--warning);">No published root categories found.</p>
                            @else
                                <div style="border:1px solid var(--border);border-radius:8px;padding:12px;max-height:360px;overflow-y:auto">
                                    @foreach ($rootCategories as $root)
                                        @php $hasChildren = $root->children->isNotEmpty(); @endphp
                                        <div x-data="{ open: {{ in_array((string) $root->id, $currentCategoryIds) ? 'true' : 'false' }} }"
                                             style="margin-bottom:4px;border:1px solid var(--border-subtle,rgba(255,255,255,.06));border-radius:6px;overflow:hidden">

                                            <label style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;background:{{ in_array((string) $root->id, $currentCategoryIds) ? 'var(--primary-50,#fff7ed)' : 'transparent' }}">
                                                <input type="checkbox"
                                                    name="categoryIds[]"
                                                    value="{{ $root->id }}"
                                                    class="field-checkbox"
                                                    {{ in_array((string) $root->id, $currentCategoryIds) ? 'checked' : '' }}
                                                    style="width:15px;height:15px;flex-shrink:0">
                                                <span style="flex:1;font-size:13px;color:var(--t1)">
                                                    {{ $root->translationValue('name') ?: $root->id }}
                                                </span>
                                                @if($hasChildren)
                                                    <button type="button" @click.prevent="open = !open"
                                                        style="background:none;border:none;cursor:pointer;color:var(--t3);padding:2px">
                                                        <svg style="width:14px;height:14px;transition:transform .2s"
                                                            :style="open ? 'transform:rotate(90deg)' : ''"
                                                            viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                                clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </label>

                                            @if($hasChildren)
                                                <div x-show="open" x-cloak
                                                    style="background:var(--bg-subtle,rgba(255,255,255,.02));border-top:1px solid var(--border-subtle);padding:6px 12px 6px 28px">
                                                    @foreach($root->children as $child)
                                                        @php $hasGrandchildren = $child->children->isNotEmpty(); @endphp
                                                        <div x-data="{ open2: false }" style="margin-bottom:2px">
                                                            <div style="display:flex;align-items:center;gap:8px;padding:4px 0">
                                                                <span style="width:12px;border-top:1px solid var(--border-subtle);flex-shrink:0"></span>
                                                                <span style="font-size:12px;color:var(--t2);flex:1">
                                                                    {{ $child->translationValue('name') ?: $child->id }}
                                                                </span>
                                                                @if($hasGrandchildren)
                                                                    <button type="button" @click.prevent="open2 = !open2"
                                                                        style="background:none;border:none;cursor:pointer;color:var(--t3);padding:2px">
                                                                        <svg style="width:12px;height:12px;transition:transform .2s"
                                                                            :style="open2 ? 'transform:rotate(90deg)' : ''"
                                                                            viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd"
                                                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                                                clip-rule="evenodd"/>
                                                                        </svg>
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            @if($hasGrandchildren)
                                                                <div x-show="open2" x-cloak style="margin-left:20px">
                                                                    @foreach($child->children as $grand)
                                                                        <div style="display:flex;align-items:center;gap:8px;padding:3px 0">
                                                                            <span style="width:10px;border-top:1px solid var(--border-subtle);flex-shrink:0"></span>
                                                                            <span style="font-size:11px;color:var(--t3)">
                                                                                {{ $grand->translationValue('name') ?: $grand->id }}
                                                                            </span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <p style="font-size:11px;color:var(--t3);margin-top:6px">
                                    Check root categories. Sub-categories are synced automatically.
                                </p>
                            @endif
                            @error('categoryIds') <div class="field-error">{{ $message }}</div> @enderror
                            @error('categoryIds.*') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Primary Language</label>
                            <x-select name="primaryLanguageId"
                                class="{{ $errors->has('primaryLanguageId') ? 'is-invalid' : '' }}">
                                <option value="">No language</option>
                                @foreach ($languages as $language)
                                    <option value="{{ $language->id }}"
                                        {{ (string) old('primaryLanguageId', $tenant->primary_language_id ?? '') === (string) $language->id ? 'selected' : '' }}>
                                        {{ $language->name }}</option>
                                @endforeach
                            </x-select>
                            @error('primaryLanguageId') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Package</label>
                            <x-select name="packageId" class="{{ $errors->has('packageId') ? 'is-invalid' : '' }}">
                                <option value="">No package</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}"
                                        {{ (string) old('packageId', $tenant->package_id ?? '') === (string) $package->id ? 'selected' : '' }}>
                                        {{ $package->name }}</option>
                                @endforeach
                            </x-select>
                            @error('packageId') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Trial Ends At</label>
                            <x-input type="datetime-local" name="trialEndsAt"
                                class="{{ $errors->has('trialEndsAt') ? 'is-invalid' : '' }}"
                                value="{{ old('trialEndsAt', $tenant->trial_ends_at?->format('Y-m-d\TH:i') ?? '') }}" />
                            @error('trialEndsAt') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Profit Percentage (%)</label>
                            <x-input type="number" step="0.01" min="0" max="1000" name="profit_percentage"
                                class="{{ $errors->has('profit_percentage') ? 'is-invalid' : '' }}"
                                value="{{ $f('profit_percentage', 0) }}" placeholder="0" />
                            <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Applied to central
                                product prices when syncing the catalog to this tenant.</p>
                            @error('profit_percentage') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Primary Domain</label>
                            <x-input type="text" name="domain" class="{{ $errors->has('domain') ? 'is-invalid' : '' }}"
                                value="{{ old('domain', $currentDomain ?? '') }}" placeholder="store.example.com" />
                            @error('domain') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">
                                {{ $isEdit ? 'New Password' : 'Password *' }}
                            </label>
                            <x-input type="password" name="password"
                                class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                                placeholder="{{ $isEdit ? 'Leave blank to keep current password' : 'Set the initial admin password' }}" />
                            @error('password') <div class="field-error">{{ $message }}</div> @enderror
                            @if ($isEdit)
                                <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Leave blank to keep
                                    the existing password unchanged.</p>
                            @endif
                        </div>

                        @if ($isEdit)
                            <div style="grid-column:1 / -1">
                                <label class="field-label">Logo</label>
                                <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
                                    <div style="flex-shrink:0">
                                        @if ($currentLogoUrl)
                                            <img src="{{ $currentLogoUrl }}" alt="{{ $tenant->name }}"
                                                style="width:72px;height:72px;object-fit:contain;border-radius:8px;border:1px solid var(--border-color,#e5e7eb);background:#fff;">
                                        @else
                                            <div
                                                style="width:72px;height:72px;border-radius:8px;border:1px solid var(--border-color,#e5e7eb);background:var(--surface-2,#f3f4f6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:26px;font-weight:700;color:var(--text-muted,#9ca3af);">
                                                {{ strtoupper(substr($tenant->name ?? '', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div style="flex:1;min-width:200px">
                                        <input type="file" accept="image/*" name="logoUpload"
                                            class="field-control {{ $errors->has('logoUpload') ? 'is-invalid' : '' }}"
                                            style="padding:6px">
                                        @error('logoUpload') <div class="field-error">{{ $message }}</div> @enderror
                                        <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">JPG, PNG,
                                            GIF, or WebP · Max 2 MB. Leave blank to keep the existing logo.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-card-collapse>

                <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
                    <a href="{{ route('admin.plans.users') }}" class="btn btn-secondary">Back to Tenants</a>
                    <button type="submit" class="btn btn-primary">Save Tenant</button>
                </div>
            </form>
        </div>

        {{-- ── Billing & Plans Tab ──────────────────────────────────────────── --}}
        @if ($isEdit)
            <div x-show="tab === 'billing'" x-cloak>
                @livewire('admin.plan.assign-package-modal', ['tenantId' => $tenant->id])

                <div class="card form-card" style="padding:0;overflow:hidden;margin-top:20px">
                    <div style="padding:16px 24px;border-bottom:1px solid var(--border)">
                        <div class="panel-title">Billing History</div>
                        <p class="panel-copy" style="margin-top:2px">All payment transactions for this tenant.</p>
                    </div>

                    @if ($billingHistory->isEmpty())
                        <div style="padding:40px 24px;text-align:center;color:var(--t3);font-size:13px">
                            No billing records found.
                        </div>
                    @else
                        <div style="overflow-x:auto">
                            <table class="data-table" style="min-width:700px">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                        <th>Payment Method</th>
                                        <th>Date</th>
                                        <th>Expires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($billingHistory as $log)
                                        @php
                                            $expireDate = null;
                                            if ($log->paid_at && $log->package) {
                                                $expireDate = match ($log->package->term?->value) {
                                                    'monthly' => $log->paid_at->copy()->addMonth(),
                                                    'quarterly' => $log->paid_at->copy()->addMonths(3),
                                                    'yearly' => $log->paid_at->copy()->addYear(),
                                                    default => null,
                                                };
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <span style="font-family:monospace;font-size:12px;color:var(--t2)">
                                                    {{ $log->reference ? substr($log->reference, 0, 8) : '—' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($log->package)
                                                    <span style="font-weight:500">{{ $log->package->name }}</span>
                                                @else
                                                    <span style="color:var(--t3)">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ((float) $log->amount === 0.0)
                                                    <span style="color:var(--t2)">Free</span>
                                                @else
                                                    {{ number_format((float) $log->amount, 2) }}
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $statusColor = match ($log->status->value) {
                                                        'paid' => 'var(--green, #22c55e)',
                                                        'failed' => 'var(--red, #ef4444)',
                                                        'refunded' => 'var(--orange, #f97316)',
                                                        default => 'var(--t3)',
                                                    };
                                                @endphp
                                                <span style="font-size:12px;font-weight:600;color:{{ $statusColor }}">
                                                    {{ $log->status->label() }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="color:var(--t2);font-size:13px">
                                                    {{ $log->gateway ? ucfirst($log->gateway) : '—' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size:13px;color:var(--t2)">
                                                    {{ $log->paid_at ? $log->paid_at->format('M d, Y') : $log->created_at->format('M d, Y') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size:13px;color:var(--t2)">
                                                    {{ $expireDate ? $expireDate->format('M d, Y') : '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</main>
@endsection
