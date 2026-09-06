<div wire:poll.15s="refresh" style="padding:12px 14px;border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
    <button type="button" wire:click="toggle" style="width:100%;background:transparent;border:none;padding:0;cursor:pointer;display:flex;flex-direction:column;gap:6px;text-align:left;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--t3);">
                Account Setup: {{ $progress['percent'] }}% Complete
            </span>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                style="color:var(--t3);transform:rotate({{ $expanded ? '180deg' : '0deg' }});transition:transform .15s;">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </div>
        <div style="height:6px;border-radius:999px;background:var(--elevated);overflow:hidden;">
            <div style="height:100%;border-radius:999px;background:{{ $progress['percent'] >= 100 ? 'var(--green)' : 'var(--cyan)' }};width:{{ $progress['percent'] }}%;transition:width .3s;"></div>
        </div>
    </button>

    @if ($expanded)
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px;">
            @foreach ($progress['steps'] as $step)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12px;">
                    <span style="display:flex;align-items:center;gap:6px;color:{{ $step['done'] ? 'var(--t2)' : 'var(--t3)' }};{{ $step['done'] ? 'text-decoration:line-through;' : '' }}">
                        @if ($step['done'])
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="var(--green)" stroke-width="3" style="flex-shrink:0;">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        @else
                            <span style="width:13px;height:13px;border-radius:50%;border:1.5px solid var(--border);flex-shrink:0;"></span>
                        @endif
                        {{ $step['label'] }}
                    </span>

                    @unless ($step['done'])
                        @if ($step['key'] === 'email_verified')
                            <form method="POST" action="{{ route('tenant.verification.send') }}" style="margin:0;">
                                @csrf
                                <button type="submit" style="font-size:11px;color:var(--cyan);background:none;border:none;padding:0;cursor:pointer;white-space:nowrap;">{{ $step['action_label'] }}</button>
                            </form>
                        @else
                            <a href="{{ $step['action_url'] }}" style="font-size:11px;color:var(--cyan);white-space:nowrap;">{{ $step['action_label'] }}</a>
                        @endif
                    @endunless
                </div>
            @endforeach
        </div>
    @endif
</div>
