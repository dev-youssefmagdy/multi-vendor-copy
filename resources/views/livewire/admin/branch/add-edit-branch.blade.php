<main id="mn">
    <form wire:submit="save" class="page-stack">
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
                        <x-input type="text" wire:model.defer="name" placeholder="e.g. Main Branch, Cairo Warehouse" />
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Code *</label>
                        <x-input type="text" wire:model.defer="code" placeholder="e.g. main, cairo-1" />
                        <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Unique slug-style
                            identifier</p>
                        @error('code') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Phone</label>
                        <x-input type="text" wire:model.defer="phone" placeholder="+20 10 0000 0000" />
                        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Email</label>
                        <x-input type="email" wire:model.defer="email" placeholder="branch@example.com" />
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="span-2">
                        <label class="field-label">Address</label>
                        <x-input type="text" wire:model.defer="address" placeholder="Street address" />
                        @error('address') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">City</label>
                        <x-input type="text" wire:model.defer="city" placeholder="City" />
                        @error('city') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Country</label>
                        <x-input type="text" wire:model.defer="country" placeholder="Country" />
                        @error('country') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="field-flag-grid" style="margin-top:16px">
                    <label class="toggle-field">
                        <input type="checkbox" wire:model.defer="isDefault">
                        <span>Default Branch</span>
                    </label>
                    <label class="toggle-field">
                        <input type="checkbox" wire:model.defer="isActive">
                        <span>Active</span>
                    </label>
                </div>
            </x-card-collapse>

            {{-- ── Shipping Zones ───────────────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-12" title="Shipping Zones"
                subtitle="Define one shipping zone per country with weight-based rate bands." :start-open="true">
                <div class="panel-head" style="margin-bottom:16px">
                    <div></div>
                    <x-btn type="button" variant="secondary" class="btn-sm" wire:click="addShippingZone">Add
                        Zone</x-btn>
                </div>

                <div class="vgroup-list">
                    @foreach ($shippingZones as $zoneIndex => $zone)
                        <div class="vgroup-card" wire:key="zone-{{ $zoneIndex }}">
                            <div class="vgroup-header">
                                <span class="vgroup-title">
                                    Zone {{ $loop->iteration }}
                                    @if (!empty($zone['name']))
                                        &mdash; {{ $zone['name'] }}
                                    @endif
                                </span>
                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                    wire:click="removeShippingZone({{ $zoneIndex }})">
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
                                        <select class="field-control"
                                            wire:model.defer="shippingZones.{{ $zoneIndex }}.country_id">
                                            <option value="">— Select country —</option>
                                            @foreach ($countries as $c)
                                                <option value="{{ $c->id }}">
                                                    {{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("shippingZones.{$zoneIndex}.country_id") <p class="field-error">
                                            {{ $message }}
                                        </p> @enderror
                                    </div>

                                    <div>
                                        <label class="field-label">Zone Name *</label>
                                        <x-input type="text" wire:model.defer="shippingZones.{{ $zoneIndex }}.name"
                                            placeholder="e.g. Egypt Delivery" />
                                        @error("shippingZones.{$zoneIndex}.name") <p class="field-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="field-label">Code</label>
                                        <x-input type="text" wire:model.defer="shippingZones.{{ $zoneIndex }}.code"
                                            placeholder="auto-generated from name" />
                                        @error("shippingZones.{$zoneIndex}.code") <p class="field-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="field-label">Currency Code *</label>
                                        <x-input type="text" wire:model.defer="shippingZones.{{ $zoneIndex }}.currency_code"
                                            placeholder="USD" maxlength="3" style="text-transform:uppercase" />
                                        @error("shippingZones.{$zoneIndex}.currency_code") <p class="field-error">
                                            {{ $message }}
                                        </p> @enderror
                                    </div>

                                    <div>
                                        <label class="field-label">Status</label>
                                        <x-select wire:model.defer="shippingZones.{{ $zoneIndex }}.status">
                                            @foreach ($statusOptions as $statusOption)
                                                <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                        @error("shippingZones.{$zoneIndex}.status") <p class="field-error">{{ $message }}
                                        </p> @enderror
                                    </div>
                                </div>

                                {{-- Rates --}}
                                <div class="panel-head" style="margin-bottom:10px">
                                    <h4 class="panel-title" style="font-size:13px">Shipping Rates</h4>
                                    <x-btn type="button" variant="secondary" class="btn-sm"
                                        wire:click="addRate({{ $zoneIndex }})">Add Rate</x-btn>
                                </div>

                                <div class="vgroup-list">
                                    @foreach ($zone['rates'] ?? [] as $rateIndex => $rate)
                                        <div class="vgroup-card" style="background:var(--surface-2)"
                                            wire:key="zone-{{ $zoneIndex }}-rate-{{ $rateIndex }}">
                                            <div class="vgroup-header">
                                                <span class="vgroup-title" style="font-size:12px">Rate
                                                    {{ $loop->iteration }}</span>
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="removeRate({{ $zoneIndex }}, {{ $rateIndex }})">Remove</button>
                                            </div>
                                            <div class="vgroup-body">
                                                <div class="form-grid form-grid-2" style="margin-bottom:8px">
                                                    <div class="span-2">
                                                        <label class="field-label">Name *</label>
                                                        <x-input type="text"
                                                            wire:model.defer="shippingZones.{{ $zoneIndex }}.rates.{{ $rateIndex }}.name"
                                                            placeholder="e.g. Standard, Express" />
                                                        @error("shippingZones.{$zoneIndex}.rates.{$rateIndex}.name") <p
                                                        class="field-error">{{ $message }}</p> @enderror
                                                    </div>
                                                </div>
                                                <div class="form-grid form-grid-3">
                                                    <div>
                                                        <label class="field-label">Min Weight (Grams)</label>
                                                        <x-input type="number" step="0.01" min="0"
                                                            wire:model.defer="shippingZones.{{ $zoneIndex }}.rates.{{ $rateIndex }}.min_weight"
                                                            placeholder="0.00" />
                                                    </div>
                                                    <div>
                                                        <label class="field-label">Max Weight (Grams)</label>
                                                        <x-input type="number" step="0.01" min="0"
                                                            wire:model.defer="shippingZones.{{ $zoneIndex }}.rates.{{ $rateIndex }}.max_weight"
                                                            placeholder="∞" />
                                                    </div>
                                                    <div>
                                                        <label class="field-label">Price *</label>
                                                        <x-input type="number" step="0.01" min="0"
                                                            wire:model.defer="shippingZones.{{ $zoneIndex }}.rates.{{ $rateIndex }}.price"
                                                            placeholder="0.00" />
                                                        @error("shippingZones.{$zoneIndex}.rates.{$rateIndex}.price") <p
                                                        class="field-error">{{ $message }}</p> @enderror
                                                    </div>
                                                </div>
                                                <div class="field-flag-grid" style="margin-top:8px">
                                                    <label class="toggle-field">
                                                        <input type="checkbox"
                                                            wire:model.defer="shippingZones.{{ $zoneIndex }}.rates.{{ $rateIndex }}.is_active">
                                                        <span>Rate Active</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
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