{{-- ── Compliance Acceptance Gate ──────────────────────────────────────────
     The tour/setup wizard now live at a dedicated page (route
     tenant.onboarding) — see livewire/tenant/onboarding/page.blade.php.
     This component only gates access behind compliance acceptance and,
     once accepted, redirects first-time users to that page. --}}
<div>

    {{-- ── Compliance Acceptance Modal ─────────────────────────────────────── --}}
    @if ($showCompliance)
        <div class="ob-backdrop" role="dialog" aria-modal="true" aria-label="Compliance Acceptance">
            <div class="ob-modal fu d0" style="max-width:640px">

                {{-- Icon --}}
                <div class="ob-icon-wrap ob-icon-violet">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                {{-- Header --}}
                <div class="ob-content">
                    <h2 class="ob-title">Review &amp; Accept Compliance Documents</h2>
                    <p class="ob-description">Before accessing your vendor panel, please read and accept the following
                        documents.</p>
                </div>

                {{-- Compliance pages --}}
                @if ($compliancePagesList->isNotEmpty())
                    <div class="ob-compliance-pages">
                        @foreach ($compliancePagesList as $cp)
                            <details class="ob-compliance-item">
                                <summary class="ob-compliance-summary">{{ $cp['title'] }}</summary>
                                <div class="ob-compliance-body prose prose-sm">
                                    {!! $cp['content'] !!}
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif

                {{-- Acceptance checkbox --}}
                <label class="ob-compliance-accept-label">
                    <input type="checkbox" wire:model.live="complianceChecked" class="ob-compliance-checkbox">
                    <span>I have read and agree to all the compliance documents listed above.</span>
                </label>

                {{-- Action --}}
                <div class="ob-actions" style="justify-content:flex-end">
                    <button type="button" wire:click="acceptCompliance"
                        class="ob-btn-next {{ !$complianceChecked ? 'opacity-50 cursor-not-allowed' : '' }}"
                        @if(!$complianceChecked) aria-disabled="true" @endif>
                        Accept &amp; Continue
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
