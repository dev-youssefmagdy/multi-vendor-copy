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
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:4px;">
            @foreach ($progress['steps'] as $step)
                <div style="padding:7px 8px;border-radius:8px;{{ !$step['done'] ? 'background:var(--elevated);' : '' }}">
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:{{ $step['done'] ? 'var(--t2)' : 'var(--t1)' }};{{ $step['done'] ? 'text-decoration:line-through;' : '' }}">
                        @if ($step['done'])
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="var(--green)" stroke-width="3" style="flex-shrink:0;">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        @else
                            <span style="width:13px;height:13px;border-radius:50%;border:1.5px solid var(--border);flex-shrink:0;"></span>
                        @endif
                        {{ $step['label'] }}
                    </div>

                    @unless ($step['done'])
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;padding-left:19px;">
                            @if ($step['key'] === 'email_verified')
                                <form method="POST" action="{{ route('tenant.verification.send') }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="setup-step-action">{{ $step['action_label'] }}</button>
                                </form>
                            @else
                                <a href="{{ $step['action_url'] }}" class="setup-step-action">{{ $step['action_label'] }}</a>

                                @if ($step['key'] === 'default_pages_reviewed')
                                    <button type="button" wire:click="markPagesReviewed" class="setup-step-action setup-step-action-primary">
                                        Mark as complete
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endunless
                </div>
            @endforeach
        </div>

        <style>
            .setup-step-action {
                display: inline-flex;
                align-items: center;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
                white-space: nowrap;
                padding: 5px 9px;
                border-radius: 6px;
                border: 1px solid var(--border);
                background: var(--surface);
                color: var(--cyan);
                text-decoration: none;
                cursor: pointer;
                transition: background .15s, border-color .15s;
            }

            .setup-step-action:hover {
                background: var(--elevated);
                border-color: var(--cyan);
            }

            .setup-step-action-primary {
                background: var(--cyan);
                border-color: var(--cyan);
                color: var(--panel, #12141a);
            }

            .setup-step-action-primary:hover {
                filter: brightness(1.08);
                background: var(--cyan);
            }
        </style>
    @endif
</div>
