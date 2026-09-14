@extends('tenant.layouts.app')

@section('title', 'Get Started')

@section('content')
    @php $step = $steps[0] ?? null; @endphp

    <div class="page-head fu d0">
        <div class="page-title-row">
            <h1 class="D page-title">Get Started</h1>
            <span class="page-badge">Onboarding</span>
        </div>
    </div>

    @if(session('setup_return_url'))
        <div class="ob-setup-banner ob-setup-banner-warning">
            {{ session('setup_warning') ?? session('setup_error') }}
            <a href="{{ session('setup_return_url') . (str_contains(session('setup_return_url'), '?') ? '&' : '?') . 'skip_setup=1' }}"
                class="ob-setup-banner-skip">Skip for now</a>
        </div>
    @endif

    {{-- ── Tabs ─────────────────────────────────────────────────────────────── --}}
    <x-tenant::tabs mode="link" :active="$tab" :tabs="[
        'tour' => ['label' => 'Product Tour', 'href' => route('tenant.onboarding', ['tab' => 'tour'])],
        'setup' => [
            'label' => 'Store Setup',
            'href' => route('tenant.onboarding', ['tab' => 'setup']),
            'badge' => collect($setupItems)->where('done', true)->count() . '/' . count($setupItems),
        ],
    ]">
    </x-tenant::tabs>

    <div class="ob-page-card fu d0">

        {{-- ── Product Tour ─────────────────────────────────────────────────── --}}
        @if($tab === 'tour' && $step)
            <div class="ob-page-panel" data-onboarding-tour data-total-steps="{{ $totalSteps }}"
                data-complete-url="{{ route('tenant.onboarding.tour.complete') }}">

                <script type="application/json" id="onboarding-steps-data">{!! json_encode($steps) !!}</script>
                <template id="onboarding-icons-template">
                    @foreach(['welcome', 'dashboard', 'products', 'orders', 'analytics', 'wallet', 'storefront', 'settings', 'languages', 'payment', 'done'] as $iconName)
                        <div data-icon-name="{{ $iconName }}">@include('tenant.pages.onboarding._icons', ['name' => $iconName])</div>
                    @endforeach
                </template>

                <div class="ob-progress">
                    <div class="ob-progress-fill" data-tour-progress-fill></div>
                </div>

                <div class="ob-step-counter">
                    <span class="ob-step-pill" data-tour-step-pill>Step 1 of {{ $totalSteps }}</span>
                </div>

                <div class="ob-icon-wrap" data-tour-icon-wrap></div>

                <div class="ob-content">
                    <h2 class="ob-title" data-tour-title></h2>
                    <p class="ob-description" data-tour-description></p>
                </div>

                <div class="ob-dots" data-tour-dots>
                    @foreach($steps as $index => $_)
                        <button type="button" data-tour-dot="{{ $index }}" class="ob-dot" aria-label="Go to step {{ $index + 1 }}"></button>
                    @endforeach
                </div>

                <div class="ob-actions">
                    <button type="button" class="ob-btn-skip" data-tour-skip>Skip to Store Setup</button>
                    <button type="button" class="ob-btn-next" data-tour-next>
                        <span data-tour-next-label>Next</span>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" data-tour-next-icon-mid>
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" data-tour-next-icon-last hidden>
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>
                </div>

            </div>
        @endif

        {{-- ── Store Setup ──────────────────────────────────────────────────── --}}
        @if($tab === 'setup')
            <div class="ob-page-panel" data-onboarding-setup data-highlight-item="{{ $highlightItem }}">

                <div class="ob-setup-header">
                    <div class="ob-setup-header-icon">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div style="flex:1;min-width:0">
                        <h2 class="ob-setup-title">Quick Store Setup</h2>
                        <p class="ob-setup-subtitle">Complete these steps to get your store ready for customers.</p>
                    </div>
                    @php
                        $doneCount = collect($setupItems)->where('done', true)->count();
                        $totalCount = count($setupItems);
                        $pct = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;
                    @endphp
                    <div class="ob-setup-header-progress" data-setup-progress-summary>
                        <div class="ob-setup-header-progress-label" data-setup-progress-text>
                            Setup {{ $pct }}% complete — {{ $totalCount - $doneCount }} {{ \Illuminate\Support\Str::plural('step', $totalCount - $doneCount) }} remaining
                        </div>
                        <div class="ob-setup-header-progress-bar">
                            <div class="ob-setup-header-progress-fill" data-setup-progress-fill data-pct="{{ $pct }}"></div>
                        </div>
                    </div>
                </div>

                <div class="ob-setup-list" data-setup-list>
                    @foreach($setupItems as $item)
                        <div class="ob-setup-item {{ $item['done'] ? 'ob-setup-done' : '' }}" data-setup-item="{{ $item['key'] }}">

                            <div class="ob-setup-item-row">
                                <div class="ob-setup-item-status">
                                    @if($item['done'])
                                        <div class="ob-check-done">
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </div>
                                    @else
                                        <div class="ob-check-pending {{ $item['mandatory'] ? 'ob-check-mandatory' : 'ob-check-optional' }}">
                                            @if($item['mandatory'])
                                                <span class="ob-check-asterisk">*</span>
                                            @else
                                                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <circle cx="12" cy="12" r="9" />
                                                </svg>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="ob-setup-item-body">
                                    <div class="ob-setup-item-head">
                                        <span class="ob-setup-item-label">{{ $item['label'] }}</span>
                                        @if($item['mandatory'])
                                            <span class="ob-badge-mandatory">Required</span>
                                        @else
                                            <span class="ob-badge-optional">Optional</span>
                                        @endif
                                    </div>
                                    <p class="ob-setup-item-detail">{{ $item['detail'] }}</p>
                                </div>

                                @if(!$item['done'])
                                    <a href="{{ $item['action_url'] }}" class="ob-setup-action">
                                        {{ $item['action_label'] }}
                                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="9 18 15 12 9 6" />
                                        </svg>
                                    </a>
                                @else
                                    <div class="ob-setup-item-done-flag">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        Done
                                    </div>
                                @endif
                            </div>

                            <div class="ob-setup-item-content">
                                @if($item['key'] === 'logo' && !$item['done'])
                                    <x-tenant::form id="onboarding-logo-form"
                                        :action="route('tenant.onboarding.logo')"
                                        :validate="route('tenant.onboarding.logo.validate')"
                                        :files="true"
                                        success="emit:tenant:onboarding:setup-updated"
                                        class="ob-logo-form">
                                        @include('tenant.pages.store._logo-builder', [
                                            'logoMode' => $logo['logo_mode'],
                                            'logoTextAr' => $logo['logo_text_ar'],
                                            'logoTextEn' => $logo['logo_text_en'],
                                            'logoColor' => $logo['logo_color'],
                                            'logoBgColor' => $logo['logo_bg_color'],
                                            'logoShape' => $logo['logo_shape'],
                                            'logoFontAr' => $logo['logo_font_ar'],
                                            'logoFontEn' => $logo['logo_font_en'],
                                            'logoPathAr' => $logo['logo_path_ar'],
                                            'logoPathEn' => $logo['logo_path_en'],
                                            'logoFonts' => $logoFonts,
                                        ])

                                        <button type="submit" class="ob-upload-submit">
                                            <span>Save Logo</span>
                                        </button>
                                    </x-tenant::form>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>

                {{-- ── Payment Readiness ───────────────────────────────────────── --}}
                <div data-payment-readiness-block @if($paymentReadinessSkipped) hidden @endif>
                    <div class="ob-payment-readiness-card">
                        <div class="ob-payment-readiness-head">
                            <div>
                                <h3 class="ob-payment-readiness-title">Payment Readiness</h3>
                                <p class="ob-payment-readiness-subtitle">Whether your storefront is set up to actually get paid, based on connected gateways and your target countries.</p>
                            </div>
                            <button type="button" class="ob-btn-dismiss" style="flex-shrink:0"
                                data-action-url="{{ route('tenant.onboarding.payment-readiness.skip') }}"
                                data-success="emit:tenant:onboarding:setup-updated"
                                data-payment-readiness-skip>
                                Mark as complete
                            </button>
                        </div>

                        <div class="legend-list">
                            @foreach($paymentReadinessItems as $item)
                                <div class="legend-row">
                                    <div class="legend-meta">
                                        <span class="dot {{ $item['ready'] ? 'dot-green' : 'dot-amber' }}"></span>
                                        <span class="text-t2">{{ $item['label'] }}</span>
                                    </div>
                                    <span class="legend-value">
                                        <span class="badge {{ $item['ready'] ? 'badge-green' : 'badge-amber' }}">{{ $item['ready'] ? 'Ready' : 'Needs setup' }}</span>
                                    </span>
                                </div>
                                <p class="panel-copy" style="margin:2px 0 8px;">{{ $item['caption'] }}</p>
                            @endforeach
                        </div>

                        @if(collect($paymentReadinessItems)->where('ready', false)->isNotEmpty())
                            <div class="ob-pr-cta">
                                <a href="{{ route('tenant.settings.payment-gateways') }}" class="ob-setup-action">
                                    Configure Payment Gateways
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="9 18 15 12 9 6" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div data-payment-readiness-done @unless($paymentReadinessSkipped) hidden @endunless>
                    <div class="ob-setup-item ob-setup-done" style="margin-top:12px;">
                        <div class="ob-setup-item-row">
                            <div class="ob-setup-item-status">
                                <div class="ob-check-done">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ob-setup-item-body">
                                <div class="ob-setup-item-head">
                                    <span class="ob-setup-item-label">Payment Readiness</span>
                                    <span class="ob-badge-optional">Reviewed</span>
                                </div>
                                <p class="ob-setup-item-detail">You've reviewed your payment readiness status.</p>
                            </div>
                            <div class="ob-setup-item-done-flag">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Done
                            </div>
                        </div>
                    </div>
                </div>

                <div data-setup-footer-done @unless($allItemsDone) hidden @endunless>
                    <div class="ob-setup-footer ob-setup-footer-done">
                        <div class="ob-setup-all-done">
                            <div class="ob-setup-done-icon">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                            <div>
                                <div class="ob-setup-done-title">Your store is ready!</div>
                                <div class="ob-setup-done-copy">All setup tasks are complete. You're good to go.</div>
                            </div>
                        </div>
                        <button type="button" class="ob-btn-next" style="flex-shrink:0"
                            data-action-url="{{ route('tenant.onboarding.dismiss') }}">
                            Go to Dashboard
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <polyline points="9 18 15 12 9 6" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div data-setup-footer-pending @if($allItemsDone) hidden @endif>
                    <div class="ob-setup-footer">
                        <p class="ob-setup-footer-note">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="ob-inline-icon">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            Required items must be completed before your store goes live. You can come back to this page anytime from the sidebar.
                        </p>
                        <button type="button" class="ob-btn-dismiss" data-action-url="{{ route('tenant.onboarding.dismiss') }}">
                            I'll do this later
                        </button>
                    </div>
                </div>

            </div>
        @endif

    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/onboarding/index.js')
@endpush
