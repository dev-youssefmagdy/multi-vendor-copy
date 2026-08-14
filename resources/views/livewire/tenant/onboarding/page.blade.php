{{-- ── Onboarding Page (Tour + Store Setup, tab-based, always reachable) ──── --}}
<main id="mn">
    @php $step = $steps[$currentStep] ?? null; @endphp

    <div class="page-head fu d0">
        <div class="page-title-row">
            <h1 class="D page-title">Get Started</h1>
            <span class="page-badge">Onboarding</span>
        </div>
    </div>

    @if (session('setup_error'))
        <div class="ob-setup-banner ob-setup-banner-error">{{ session('setup_error') }}</div>
    @elseif (session('setup_warning'))
        <div class="ob-setup-banner ob-setup-banner-warning">
            {{ session('setup_warning') }}
            @if (session('setup_return_url'))
                <a href="{{ session('setup_return_url') . (str_contains(session('setup_return_url'), '?') ? '&' : '?') . 'skip_setup=1' }}"
                    class="ob-setup-banner-skip">Skip for now</a>
            @endif
        </div>
    @endif

    {{-- ── Tabs ─────────────────────────────────────────────────────────────── --}}
    <div class="ob-page-tabs">
        <a href="{{ route('tenant.onboarding', ['tab' => 'tour']) }}" wire:navigate
            class="ob-page-tab {{ $tab === 'tour' ? 'ob-page-tab-active' : '' }}">
            Product Tour
        </a>
        <a href="{{ route('tenant.onboarding', ['tab' => 'setup']) }}" wire:navigate
            class="ob-page-tab {{ $tab === 'setup' ? 'ob-page-tab-active' : '' }}">
            Store Setup
            @php $doneCount = collect($setupItems)->where('done', true)->count(); @endphp
            <span class="ob-page-tab-count">{{ $doneCount }}/{{ count($setupItems) }}</span>
        </a>
    </div>

    <div class="ob-page-card fu d0">

        {{-- ── Product Tour ─────────────────────────────────────────────────── --}}
        @if ($tab === 'tour' && $step)
            <div class="ob-page-panel">

                <div class="ob-progress">
                    <div class="ob-progress-fill" style="width: {{ (($currentStep + 1) / $totalSteps) * 100 }}%"></div>
                </div>

                <div class="ob-step-counter">
                    <span class="ob-step-pill">Step {{ $currentStep + 1 }} of {{ $totalSteps }}</span>
                </div>

                <div class="ob-icon-wrap ob-icon-{{ $step['color'] ?? 'cyan' }}">
                    @include('livewire.tenant.onboarding.icons', ['name' => $step['icon'] ?? 'welcome'])
                </div>

                <div class="ob-content">
                    <h2 class="ob-title">{{ $step['title'] }}</h2>
                    <p class="ob-description">{{ $step['description'] }}</p>
                </div>

                <div class="ob-dots">
                    @foreach ($steps as $index => $_)
                        <button type="button" wire:click="$set('currentStep', {{ $index }})"
                            class="ob-dot {{ $index === $currentStep ? 'ob-dot-active' : '' }} {{ $index < $currentStep ? 'ob-dot-done' : '' }}"
                            aria-label="Go to step {{ $index + 1 }}"></button>
                    @endforeach
                </div>

                <div class="ob-actions">
                    <button type="button" wire:click="skipTour" class="ob-btn-skip">
                        Skip to Store Setup
                    </button>
                    <button type="button" wire:click="nextStep" class="ob-btn-next">
                        @if ($currentStep < $totalSteps - 1)
                            Next
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <polyline points="9 18 15 12 9 6" />
                            </svg>
                        @else
                            Continue to Setup
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        @endif
                    </button>
                </div>

            </div>
        @endif

        {{-- ── Store Setup ──────────────────────────────────────────────────── --}}
        @if ($tab === 'setup')
            <div class="ob-page-panel">

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
                    <div style="flex-shrink:0;text-align:right">
                        <div style="font-size:11px;font-weight:700;color:var(--cyan);letter-spacing:.02em">
                            Setup {{ $pct }}% complete — {{ $totalCount - $doneCount }} {{ \Illuminate\Support\Str::plural('step', $totalCount - $doneCount) }} remaining
                        </div>
                        <div style="margin-top:4px;width:100%;height:4px;border-radius:4px;background:var(--border);overflow:hidden">
                            <div style="width:{{ $pct }}%;height:100%;background:linear-gradient(90deg,var(--cyan),var(--violet));border-radius:4px;transition:width .4s"></div>
                        </div>
                    </div>
                </div>

                <div class="ob-setup-list">
                    @foreach ($setupItems as $item)
                        <div class="ob-setup-item {{ $item['done'] ? 'ob-setup-done' : '' }}">

                            <div class="ob-setup-item-row">
                                <div class="ob-setup-item-status">
                                    @if ($item['done'])
                                        <div class="ob-check-done">
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="3">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </div>
                                    @else
                                        <div
                                            class="ob-check-pending {{ $item['mandatory'] ? 'ob-check-mandatory' : 'ob-check-optional' }}">
                                            @if ($item['mandatory'])
                                                <span class="ob-check-asterisk">*</span>
                                            @else
                                                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <circle cx="12" cy="12" r="9" />
                                                </svg>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="ob-setup-item-body">
                                    <div class="ob-setup-item-head">
                                        <span class="ob-setup-item-label">{{ $item['label'] }}</span>
                                        @if ($item['mandatory'])
                                            <span class="ob-badge-mandatory">Required</span>
                                        @else
                                            <span class="ob-badge-optional">Optional</span>
                                        @endif
                                    </div>
                                    <p class="ob-setup-item-detail">{{ $item['detail'] }}</p>
                                </div>

                                @if (!$item['done'])
                                    <a href="{{ $item['action_url'] }}" class="ob-setup-action">
                                        {{ $item['action_label'] }}
                                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <polyline points="9 18 15 12 9 6" />
                                        </svg>
                                    </a>
                                @else
                                    <div style="flex-shrink:0;display:flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:var(--green)">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        Done
                                    </div>
                                @endif
                            </div>

                            <div class="ob-setup-item-content">
                                @if ($item['key'] === 'logo' && !$item['done'])
                                    <form wire:submit.prevent="saveLogo" class="ob-logo-form">
                                        @include('livewire.tenant.store.partials.logo-builder')

                                        <button type="submit" class="ob-upload-submit"
                                                wire:loading.attr="disabled" wire:target="saveLogo">
                                            <span wire:loading.remove wire:target="saveLogo" style="white-space:nowrap">Save Logo</span>
                                            <span wire:loading wire:target="saveLogo">Saving…</span>
                                        </button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>

                @if ($allItemsDone)
                    <div class="ob-setup-footer ob-setup-footer-done">
                        <div class="ob-setup-all-done">
                            <div class="ob-setup-done-icon">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                            <div>
                                <div class="ob-setup-done-title">Your store is ready!</div>
                                <div class="ob-setup-done-copy">All setup tasks are complete. You're good to go.</div>
                            </div>
                        </div>
                        <button type="button" wire:click="dismissSetup" class="ob-btn-next" style="flex-shrink:0">
                            Go to Dashboard
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <polyline points="9 18 15 12 9 6" />
                            </svg>
                        </button>
                    </div>
                @else
                    <div class="ob-setup-footer">
                        <p class="ob-setup-footer-note">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                class="ob-inline-icon">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            Required items must be completed before your store goes live. You can come back to this page anytime from the sidebar.
                        </p>
                        <button type="button" wire:click="dismissSetup" class="ob-btn-dismiss">
                            I'll do this later
                        </button>
                    </div>
                @endif

            </div>

            @script
            <script>
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        $wire.$refresh();
                    }
                });
            </script>
            @endscript
        @endif

    </div>
</main>
