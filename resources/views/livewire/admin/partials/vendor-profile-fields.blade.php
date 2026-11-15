{{--
    Read-only vendor/affiliate identity + bank details.
    Expects $profile (App\Support\VendorProfile array). Optional $sections = ['identity','bank','business','documents'].
--}}
@php
    $profile = $profile ?? [];
    $sections = $sections ?? ['identity', 'bank'];
    $v = fn (string $k) => filled($profile[$k] ?? null) ? $profile[$k] : '—';
    $hasBank = \App\Support\VendorProfile::hasBankDetails($profile);
@endphp

@if (in_array('identity', $sections, true))
    <div class="form-grid form-grid-2">
        <div><span class="field-label">{{ __('Full name') }}</span><div>{{ $v('owner_name') }}</div></div>
        @if ($profile['is_vendor'] ?? false)
            <div><span class="field-label">{{ __('Store name') }}</span><div>
                {{ $v('store_name') }}
                @if (filled($profile['store_url'] ?? null))
                    · <a href="{{ $profile['store_url'] }}" target="_blank" rel="noopener">{{ __('Open store') }}</a>
                @endif
            </div></div>
            <div><span class="field-label">{{ __('Account type') }}</span><div>{{ $v('account_type') }}</div></div>
            <div><span class="field-label">{{ __('Business name') }}</span><div>{{ $v('business_name') }}</div></div>
        @endif
        <div><span class="field-label">{{ __('Email') }}</span><div>{{ $v('email') }}</div></div>
        <div><span class="field-label">{{ __('Phone') }}</span><div>{{ $v('phone') }}</div></div>
        @if ($profile['is_vendor'] ?? false)
            <div><span class="field-label">{{ __('Country') }}</span><div>{{ $v('country') }}</div></div>
            <div><span class="field-label">{{ __('City') }}</span><div>{{ $v('city') }}</div></div>
        @endif
    </div>
@endif

@if (in_array('owner', $sections, true))
    <div class="form-grid form-grid-2">
        <div><span class="field-label">{{ __('ID / Passport Number') }}</span><div>{{ $v('owner_id_number') }}</div></div>
        <div><span class="field-label">{{ __('Date of Birth') }}</span><div>{{ $v('owner_dob') }}</div></div>
        <div><span class="field-label">{{ __('Owner contact') }}</span><div>{{ $v('owner_contact') }}</div></div>
    </div>
@endif

@if (in_array('business', $sections, true))
    <div class="form-grid form-grid-2">
        <div><span class="field-label">{{ __('Company Name') }}</span><div>{{ $v('company_name') }}</div></div>
        <div><span class="field-label">{{ __('Registration Number') }}</span><div>{{ $v('registration_number') }}</div></div>
        <div><span class="field-label">{{ __('Registration Expiry') }}</span><div>{{ $v('registration_expiry') }}</div></div>
        <div><span class="field-label">{{ __('VAT / Tax Number') }}</span><div>{{ $v('vat_number') }}</div></div>
    </div>
@endif

@if (in_array('bank', $sections, true))
    @unless ($hasBank)
        <p class="field-error" style="margin:0 0 8px">{{ __('No bank details saved yet. Ask the vendor to complete the Compliance Center (Bank Account section).') }}</p>
    @endunless
    <div class="form-grid form-grid-2">
        <div><span class="field-label">{{ __('Bank Name') }}</span><div>{{ $v('bank_name') }}</div></div>
        <div><span class="field-label">{{ __('Account Holder') }}</span><div>{{ $v('bank_holder_name') }}</div></div>
        <div><span class="field-label">{{ __('Account Number') }}</span><div style="user-select:all">{{ $v('bank_account_number') }}</div></div>
        <div><span class="field-label">IBAN</span><div style="user-select:all">{{ $v('bank_iban') }}</div></div>
        <div><span class="field-label">{{ __('Currency') }}</span><div>{{ $v('bank_currency') }}</div></div>
        @if (filled($profile['paypal_email'] ?? null))
            <div><span class="field-label">PayPal</span><div>{{ $profile['paypal_email'] }}</div></div>
        @endif
    </div>
@endif

@if (in_array('documents', $sections, true))
    <div class="flex flex-wrap gap-2">
        @forelse (($profile['documents'] ?? []) as $label => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">{{ __($label) }}</a>
        @empty
            <span class="entity-subtitle">{{ __('No documents uploaded yet.') }}</span>
        @endforelse
    </div>
@endif
