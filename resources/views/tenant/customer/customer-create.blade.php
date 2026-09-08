@extends('layouts.tenant')

@section('content')
<main id="mn">

    {{-- ── Page head ──────────────────────────────────────────────────────── --}}
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Add Customer</h1>
                <span class="page-badge">CRM</span>
            </div>
            <p class="page-copy">Create a new customer record for this tenant workspace.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('tenant.customers.index') }}">← Customers</a>
        </div>
    </div>

    <div class="page-stack section-gap">
        <form method="POST" action="{{ route('tenant.customers.store') }}">
            @csrf

            <x-card-collapse title="Customer Details" subtitle="Name, contact info, status and password." :start-open="true">
                <div class="form-grid form-grid-2"
                     x-data="{
                         countryId: '{{ old('countryId') }}',
                         cities: {{ $cities->map(fn($n, $id) => ['id' => $id, 'name' => $n])->values()->toJson() }},
                         async onCountryChange(val) {
                             this.countryId = val;
                             this.cities = [];
                             if (!val) { return; }
                             const r = await fetch('{{ url('cities-by-country') }}/' + val);
                             this.cities = await r.json();
                         }
                     }">
                    <div>
                        <label class="field-label">Full Name <span class="field-required">*</span></label>
                        <x-input name="fullName" value="{{ old('fullName') }}" placeholder="Full name" />
                        @error('fullName') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Email <span class="field-required">*</span></label>
                        <x-input type="email" name="email" value="{{ old('email') }}" placeholder="customer@example.com" />
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Phone</label>
                        <x-input type="tel" name="phone" data-phone-input value="{{ old('phone') }}" placeholder="+1 555 000 0000" />
                        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Country</label>
                        <select class="field-control" name="countryId" @change="onCountryChange($event.target.value)">
                            <option value="">— select country —</option>
                            @foreach ($countries as $cId => $cName)
                                <option value="{{ $cId }}" :selected="countryId == '{{ $cId }}'">{{ $cName }}</option>
                            @endforeach
                        </select>
                        @error('countryId') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">City</label>
                        <select class="field-control" name="cityId" :disabled="!countryId">
                            <option value="">— select city —</option>
                            <template x-for="city in cities" :key="city.id">
                                <option :value="city.id"
                                    :selected="city.id == '{{ old('cityId') }}'"
                                    x-text="city.name"></option>
                            </template>
                        </select>
                        @error('cityId') <p class="field-error">{{ $message }}</p> @enderror
                        <p x-show="!countryId" style="font-size:11px; color:var(--t3); margin-top:3px;">Select a country first</p>
                        <p x-show="countryId && cities.length === 0" x-cloak style="font-size:11px; color:var(--t3); margin-top:3px;">No cities for this country yet</p>
                    </div>
                    <div class="form-grid-full">
                        <label class="field-label">Default Address</label>
                        <x-input name="address" value="{{ old('address') }}" placeholder="Short freeform address" />
                        @error('address') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Password <span class="field-required">*</span></label>
                        <x-input type="password" name="password" placeholder="Min 6 characters" autocomplete="new-password" />
                        @error('password') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Confirm Password</label>
                        <x-input type="password" name="passwordConfirmation" placeholder="Repeat password" autocomplete="new-password" />
                        @error('passwordConfirmation') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div style="display:flex; align-items:flex-end; padding-bottom:4px;">
                        <label class="toggle-field" style="cursor:pointer; display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                            <span class="field-label" style="margin:0;">Active account</span>
                        </label>
                    </div>
                </div>

                <div class="page-actions compact-actions justify-end" style="margin-top:16px;">
                    <a href="{{ route('tenant.customers.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Customer</button>
                </div>
            </x-card-collapse>
        </form>
    </div>

</main>
@endsection
