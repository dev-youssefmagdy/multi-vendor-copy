@php($progress = $shell['setupProgress'])

<div class="t-setup-progress" data-setup-progress data-url="{{ route('tenant.widgets.setup-progress') }}" data-poll="15000">
    <button type="button" class="t-setup-progress-toggle" data-setup-toggle>
        <div class="t-setup-progress-head">
            <span class="t-setup-progress-label">Account Setup: <span data-setup-percent>{{ $progress['percent'] }}</span>% Complete</span>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" class="t-setup-progress-chevron" data-setup-chevron>
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </div>
        <div class="t-setup-progress-track">
            <div class="t-setup-progress-bar {{ $progress['percent'] >= 100 ? 'is-done' : '' }}" data-setup-bar style="--p: {{ $progress['percent'] }}%"></div>
        </div>
    </button>

    <div class="t-setup-progress-steps" data-setup-steps hidden>
        @foreach($progress['steps'] as $step)
            <div class="t-setup-progress-step {{ !$step['done'] ? 'is-pending' : '' }}" data-step-key="{{ $step['key'] }}">
                <div class="t-setup-progress-step-row {{ $step['done'] ? 'is-done' : '' }}">
                    @if($step['done'])
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="var(--green)" stroke-width="3" class="t-setup-progress-check">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    @else
                        <span class="t-setup-progress-dot"></span>
                    @endif
                    {{ $step['label'] }}
                </div>

                @unless($step['done'])
                    <div class="t-setup-progress-actions">
                        @if($step['key'] === 'email_verified')
                            <form method="POST" action="{{ route('tenant.verification.send') }}" class="t-setup-progress-form" data-tenant-form data-success="none">
                                @csrf
                                <button type="submit" class="setup-step-action">{{ $step['action_label'] }}</button>
                            </form>
                        @else
                            <a href="{{ $step['action_url'] }}" class="setup-step-action">{{ $step['action_label'] }}</a>

                            @if($step['key'] === 'default_pages_reviewed')
                                <button type="button" class="setup-step-action setup-step-action-primary"
                                    data-action-url="{{ route('tenant.widgets.setup-progress.pages-reviewed') }}"
                                    data-action-method="POST"
                                    data-success="emit:tenant:setup-progress:refresh">
                                    Mark as complete
                                </button>
                            @endif
                        @endif
                    </div>
                @endunless
            </div>
        @endforeach
    </div>
</div>
