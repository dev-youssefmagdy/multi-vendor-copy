@extends('layouts.app')

@section('content')
<main id="mn" x-data="branchForm({
        zones: {{ Illuminate\Support\Js::from($shippingZones) }},
        statusOptions: {{ Illuminate\Support\Js::from(collect($statusOptions)->map(fn ($s) => $s->value)) }},
    })">
    <form method="POST" action="{{ $branch ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="page-stack">
        @csrf
        @if ($branch)
            @method('PUT')
        @endif

        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Central Commerce</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Configure branch details, shipping zones per country, and rates.</p>
            </div>
            <div class="page-actions" style="flex-shrink:0">
                <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">Back to
                    Branches</a>
                <x-btn type="submit">Save Branch</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card section-gap notice-error">
                <h3 class="panel-title">Please fix the highlighted fields</h3>
                <p class="panel-copy">The branch was not saved because some required values are missing or invalid.</p>
            </div>
        @endif

        <div class="grid gap-2">

            {{-- ── Branch Info ────────────────────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-12" title="Branch Information"
                subtitle="Name, code, contact details and location." :start-open="true">
                <div class="form-grid">
                    <div>
                        <label class="field-label">Name *</label>
                        <x-input type="text" name="name" value="{{ old('name', $branch->name ?? '') }}" placeholder="e.g. Main Branch, Cairo Warehouse" />
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Code *</label>
                        <x-input type="text" name="code" value="{{ old('code', $branch->code ?? '') }}" placeholder="e.g. main, cairo-1" />
                        <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Unique slug-style
                            identifier</p>
                        @error('code') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Phone</label>
                        <x-input type="tel" data-phone-input name="phone" value="{{ old('phone', $phone) }}" placeholder="+20 10 0000 0000" />
                        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Email</label>
                        <x-input type="email" name="email" value="{{ old('email', $branch->email ?? '') }}" placeholder="branch@example.com" />
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="span-2">
                        <label class="field-label">Address</label>
                        <x-input type="text" name="address" value="{{ old('address', $branch->address ?? '') }}" placeholder="Street address" />
                        @error('address') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">City</label>
                        <x-input type="text" name="city" value="{{ old('city', $branch->city ?? '') }}" placeholder="City" />
                        @error('city') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Country</label>
                        <x-input type="text" name="country" value="{{ old('country', $branch->country ?? '') }}" placeholder="Country" />
                        @error('country') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="field-flag-grid" style="margin-top:16px">
                    <label class="toggle-field">
                        <input type="checkbox" name="isDefault" value="1" {{ old('isDefault', $branch->is_default ?? false) ? 'checked' : '' }}>
                        <span>Default Branch</span>
                    </label>
                    <label class="toggle-field">
                        <input type="checkbox" name="isActive" value="1" {{ old('isActive', $branch->is_active ?? true) ? 'checked' : '' }}>
                        <span>Active</span>
                    </label>
                </div>
            </x-card-collapse>

            {{-- ── Shipping Zones ───────────────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-12" title="Shipping Zones"
                subtitle="Define one shipping zone per country with weight-based rate bands." :start-open="true">
                <div class="panel-head" style="margin-bottom:16px">
                    <div></div>
                    <x-btn type="button" variant="secondary" class="btn-sm" @click="addZone()">Add
                        Zone</x-btn>
                </div>

                <div class="vgroup-list">
                    <template x-for="(zone, zi) in zones" :key="zi">
                        <div class="vgroup-card">
                            <input type="hidden" :name="`shippingZones[${zi}][id]`" :value="zone.id" x-show="zone.id">

                            <div class="vgroup-header">
                                <span class="vgroup-title">
                                    <span x-text="`Zone ${zi + 1}`"></span>
                                    <template x-if="zone.name">
                                        <span x-text="' — ' + zone.name"></span>
                                    </template>
                                </span>
                                <button type="button" class="btn btn-secondary btn-sm btn-danger" @click="removeZone(zi)">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14H6L5 6" />
                                        <path d="M10 11v6M14 11v6" />
                                        <path d="M9 6V4h6v2" />
                                    </svg>
                                    Remove
                                </button>
                            </div>

                            <div class="vgroup-body">
                                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                                    <div>
                                        <label class="field-label">Country *</label>
                                        <select class="field-control" :name="`shippingZones[${zi}][country_id]`" x-model="zone.country_id">
                                            <option value="">— Select country —</option>
                                            @foreach ($countries as $c)
                                                <option value="{{ $c->id }}">
                                                    {{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="field-label">Zone Name *</label>
                                        <x-input type="text" :name="`shippingZones[${zi}][name]`" x-model="zone.name"
                                            placeholder="e.g. Egypt Delivery" />
                                    </div>

                                    <div>
                                        <label class="field-label">Code</label>
                                        <x-input type="text" :name="`shippingZones[${zi}][code]`" x-model="zone.code"
                                            placeholder="auto-generated from name" />
                                    </div>

                                    <div>
                                        <label class="field-label">Currency Code *</label>
                                        <x-input type="text" :name="`shippingZones[${zi}][currency_code]`" x-model="zone.currency_code"
                                            placeholder="USD" maxlength="3" style="text-transform:uppercase" />
                                    </div>

                                    <div>
                                        <label class="field-label">Status</label>
                                        <x-select :name="`shippingZones[${zi}][status]`" x-model="zone.status">
                                            @foreach ($statusOptions as $statusOption)
                                                <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                </div>

                                {{-- Rates --}}
                                <div class="panel-head" style="margin-bottom:10px">
                                    <h4 class="panel-title" style="font-size:13px">Shipping Rates</h4>
                                    <x-btn type="button" variant="secondary" class="btn-sm" @click="addRate(zi)">Add Rate</x-btn>
                                </div>

                                <div class="vgroup-list">
                                    <template x-for="(rate, ri) in zone.rates" :key="ri">
                                        <div class="vgroup-card" style="background:var(--surface-2)">
                                            <input type="hidden" :name="`shippingZones[${zi}][rates][${ri}][id]`" :value="rate.id" x-show="rate.id">

                                            <div class="vgroup-header">
                                                <span class="vgroup-title" style="font-size:12px" x-text="`Rate ${ri + 1}`"></span>
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    @click="removeRate(zi, ri)">Remove</button>
                                            </div>
                                            <div class="vgroup-body">
                                                <div class="form-grid form-grid-2" style="margin-bottom:8px">
                                                    <div class="span-2">
                                                        <label class="field-label">Name *</label>
                                                        <x-input type="text" :name="`shippingZones[${zi}][rates][${ri}][name]`"
                                                            x-model="rate.name" placeholder="e.g. Standard, Express" />
                                                    </div>
                                                </div>
                                                <div class="form-grid form-grid-3">
                                                    <div>
                                                        <label class="field-label">Min Weight (Grams)</label>
                                                        <x-input type="number" step="0.01" min="0"
                                                            :name="`shippingZones[${zi}][rates][${ri}][min_weight]`"
                                                            x-model="rate.min_weight" placeholder="0.00" />
                                                    </div>
                                                    <div>
                                                        <label class="field-label">Max Weight (Grams)</label>
                                                        <x-input type="number" step="0.01" min="0"
                                                            :name="`shippingZones[${zi}][rates][${ri}][max_weight]`"
                                                            x-model="rate.max_weight" placeholder="∞" />
                                                    </div>
                                                    <div>
                                                        <label class="field-label">Price *</label>
                                                        <x-input type="number" step="0.01" min="0"
                                                            :name="`shippingZones[${zi}][rates][${ri}][price]`"
                                                            x-model="rate.price" placeholder="0.00" />
                                                    </div>
                                                </div>
                                                <div class="field-flag-grid" style="margin-top:8px">
                                                    <label class="toggle-field">
                                                        <input type="hidden" :name="`shippingZones[${zi}][rates][${ri}][is_active]`" value="0">
                                                        <input type="checkbox" :name="`shippingZones[${zi}][rates][${ri}][is_active]`"
                                                            value="1" x-model="rate.is_active">
                                                        <span>Rate Active</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </x-card-collapse>

        </div>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">Back to Branches</a>
            <x-btn type="submit">Save Branch</x-btn>
        </div>
    </form>
</main>

<script>
    function branchForm({ zones, statusOptions }) {
        return {
            zones,
            emptyRate() {
                return { name: '', min_weight: '', max_weight: '', price: '0.00', is_active: true };
            },
            emptyZone() {
                return {
                    country_id: null,
                    name: '',
                    code: '',
                    currency_code: 'USD',
                    status: statusOptions[0] ?? 'active',
                    rates: [this.emptyRate()],
                };
            },
            addZone() {
                this.zones.push(this.emptyZone());
            },
            removeZone(zi) {
                this.zones.splice(zi, 1);
            },
            addRate(zi) {
                this.zones[zi].rates.push(this.emptyRate());
            },
            removeRate(zi, ri) {
                this.zones[zi].rates.splice(ri, 1);
            },
        };
    }
</script>
@endsection
