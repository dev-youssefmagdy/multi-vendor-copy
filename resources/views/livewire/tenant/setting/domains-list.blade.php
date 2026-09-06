<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <button type="button" wire:click="openAddModal" class="btn btn-primary">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                {{ __('Add Custom Domain') }}
            </button>
        </div>
    </div>

    {{-- ── Subdomain section ───────────────────────────────────────────── --}}
    @php
        $centralDomain = collect(config('tenancy.central_domains', []))->first(fn($d) => !in_array($d, ['127.0.0.1', 'localhost']));
        $subdomains    = $domains->filter(fn($d) => $centralDomain && str_ends_with($d->domain, '.' . $centralDomain));
        $customDomains = $domains->filter(fn($d) => !($centralDomain && str_ends_with($d->domain, '.' . $centralDomain)));
    @endphp

    @if($subdomains->isNotEmpty())
    <div class="card section-gap" style="padding:0;overflow:hidden;">
        <div class="table-header-shell" style="border-bottom:1px solid var(--border1);">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="si-box tone-bg-cyan">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-cyan">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <div>
                    <div class="section-title">{{ __('Subdomain') }}</div>
                    <div class="section-copy" style="margin-top:1px;">{{ __('Your auto-generated store address — always active.') }}</div>
                </div>
            </div>
            <span class="chip c-g" style="display:inline-flex;align-items:center;gap:5px;">
                <span class="dot dot-glow-green" style="width:6px;height:6px;"></span>
                {{ __('Active') }}
            </span>
        </div>

        @foreach($subdomains as $domain)
        <div style="padding:18px 20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                {{-- Domain URL display --}}
                <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                    <div style="background:var(--elevated);border:1px solid var(--border1);border-radius:10px;padding:9px 14px;display:flex;align-items:center;gap:8px;flex:1;min-width:0;max-width:420px;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--t3);flex-shrink:0;">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                        </svg>
                        <span class="text-strong" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $domain->domain }}</span>
                    </div>
                    <button type="button"
                        onclick="navigator.clipboard.writeText('https://{{ $domain->domain }}').then(()=>{this.innerHTML='<svg width=\'11\' height=\'11\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2.5\'><polyline points=\'20 6 9 17 4 12\'/></svg>';setTimeout(()=>this.innerHTML='<svg width=\'11\' height=\'11\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2\'><rect x=\'9\' y=\'9\' width=\'13\' height=\'13\' rx=\'2\'/><path d=\'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1\'/></svg>',1800)})"
                        title="{{ __('Copy URL') }}"
                        class="btn btn-secondary btn-sm" style="flex-shrink:0;padding:7px 10px;">
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </button>
                </div>

                {{-- Actions --}}
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <a href="https://{{ $domain->domain }}" target="_blank"
                       class="btn btn-secondary btn-sm">
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        {{ __('Visit Store') }}
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Custom Domains section ──────────────────────────────────────── --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div class="table-header-shell" style="border-bottom:1px solid var(--border1);">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="si-box tone-bg-violet">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-violet">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <div>
                    <div class="section-title">{{ __('Custom Domains') }}</div>
                    <div class="section-copy" style="margin-top:1px;">{{ __('Point your own domain to this store.') }}</div>
                </div>
            </div>
            @if($domainRequests->isNotEmpty())
                <span style="font-size:12px;color:var(--t3);">{{ $domainRequests->count() }} {{ __('domain(s)') }}</span>
            @endif
        </div>

        @forelse($domainRequests as $req)
            @php
                $statusCfg = match($req->status->value ?? $req->status) {
                    'connected' => ['c-g',  'dot-glow-green', __('Connected')],
                    'pending'   => ['c-a',  'dot-amber',      __('Pending Review')],
                    'removed'   => ['',     'dot-muted',      __('Removed')],
                    default     => ['',     'dot-muted',      $req->status],
                };
                [$chipCls, $dotCls, $statusLabel] = $statusCfg;
                $isConnected = ($req->status->value ?? $req->status) === 'connected';
                $isPending   = ($req->status->value ?? $req->status) === 'pending';
                $showDns     = $dnsCheckResult && ($dnsCheckResult['domain'] ?? '') === $req->domain;
            @endphp

            <div style="border-bottom:1px solid var(--border1);">
                {{-- Domain row --}}
                <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    {{-- Icon + name + meta --}}
                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                        <div class="si-box {{ $isConnected ? 'tone-bg-green' : ($isPending ? 'tone-bg-amber' : '') }}"
                             style="{{ !$isConnected && !$isPending ? 'background:var(--elevated);' : '' }}">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                 class="{{ $isConnected ? 'text-green' : ($isPending ? 'text-amber' : 'icon-t3') }}">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                            </svg>
                        </div>
                        <div style="min-width:0;">
                            <div class="text-strong" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $req->domain }}</div>
                            <div class="td-date">{{ __('Added') }} {{ $req->requested_at?->diffForHumans() }}</div>
                        </div>
                    </div>

                    {{-- Status chip --}}
                    <span class="chip {{ $chipCls }}" style="display:inline-flex;align-items:center;gap:5px;flex-shrink:0;">
                        <span class="dot {{ $dotCls }}" style="width:6px;height:6px;"></span>
                        {{ $statusLabel }}
                    </span>

                    {{-- Actions --}}
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        {{-- Check DNS --}}
                        <button type="button"
                            wire:click="checkDns('{{ $req->domain }}')"
                            wire:loading.attr="disabled"
                            wire:target="checkDns('{{ $req->domain }}')"
                            class="btn btn-secondary btn-sm">
                            <span wire:loading.remove wire:target="checkDns('{{ $req->domain }}')">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </span>
                            <span wire:loading wire:target="checkDns('{{ $req->domain }}')">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;animation:spin 1s linear infinite;">
                                    <path d="M21 12a9 9 0 1 1-6.22-8.56"/>
                                </svg>
                            </span>
                            {{ __('Check DNS') }}
                        </button>

                        {{-- Visit (connected only) --}}
                        @if($isConnected)
                            <a href="https://{{ $req->domain }}" target="_blank" class="btn btn-secondary btn-sm">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                                {{ __('Visit') }}
                            </a>
                        @endif

                        {{-- Edit --}}
                        <button type="button"
                            wire:click="openEditModal({{ $req->id }})"
                            class="btn btn-secondary btn-sm">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            {{ __('Edit') }}
                        </button>

                        {{-- Delete --}}
                        <button type="button"
                            wire:click="removeDomain({{ $req->id }})"
                            wire:confirm="{{ __('Delete this domain? This cannot be undone.') }}"
                            wire:loading.attr="disabled"
                            wire:target="removeDomain({{ $req->id }})"
                            class="btn btn-secondary btn-sm"
                            style="color:var(--red);border-color:rgba(255,77,106,.22);">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>

                {{-- DNS check result panel --}}
                @if($showDns)
                <div style="padding:0 20px 16px;">
                    <div style="background:var(--elevated);border:1px solid {{ $dnsCheckResult['connected'] ? 'rgba(34,197,94,.2)' : 'rgba(251,191,36,.2)' }};border-radius:12px;padding:16px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                            @if($dnsCheckResult['connected'])
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" style="color:var(--green);flex-shrink:0;">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <span class="text-strong" style="font-size:13px;color:var(--green);">{{ __('Domain is fully connected') }}</span>
                            @else
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" style="color:var(--amber);flex-shrink:0;">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <span class="text-strong" style="font-size:13px;color:var(--amber);">{{ __('DNS not fully configured yet') }}</span>
                            @endif
                        </div>

                        @if(count($dnsCheckResult['checks']))
                            <div style="display:flex;flex-direction:column;gap:7px;">
                                @foreach($dnsCheckResult['checks'] as $check)
                                <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--bg);border:1px solid var(--border1);border-radius:8px;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                         style="color:var(--{{ $check['ok'] ? 'green' : 'red' }});flex-shrink:0;">
                                        @if($check['ok'])
                                            <polyline points="20 6 9 17 4 12"/>
                                        @else
                                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                        @endif
                                    </svg>
                                    <span style="font-size:11px;color:var(--t3);flex-shrink:0;font-weight:600;letter-spacing:.03em;">{{ $check['type'] }}</span>
                                    <span style="font-size:11px;color:var(--t2);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <span style="color:var(--t3);">{{ $check['name'] }}</span>
                                        <span style="margin:0 4px;color:var(--t3);">→</span>
                                        <code style="font-size:11px;">{{ $check['value'] }}</code>
                                    </span>
                                    <span style="font-size:10px;font-weight:600;color:var(--{{ $check['ok'] ? 'green' : 'red' }});flex-shrink:0;">
                                        {{ $check['ok'] ? __('OK') : __('MISSING') }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p style="font-size:12px;color:var(--t3);margin:0;">{{ __('No DNS records defined to check against.') }}</p>
                        @endif

                        @if(!$dnsCheckResult['connected'])
                        <p style="font-size:12px;color:var(--t3);margin:12px 0 0;">
                            {{ __('DNS propagation can take up to 48 hours. Check again after updating your records.') }}
                        </p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" style="color:var(--t3);opacity:.4;">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <div class="empty-state-title">{{ __('No custom domains yet') }}</div>
                <div class="empty-state-copy">{{ __('Add your own domain to give your store a branded URL.') }}</div>
                <button type="button" wire:click="openAddModal" class="btn btn-primary" style="margin-top:14px;">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    {{ __('Add Custom Domain') }}
                </button>
            </div>
        @endforelse
    </div>

    {{-- ── DNS Setup guide ─────────────────────────────────────────────── --}}
    <div class="card" style="padding:18px 20px;">
        <div style="display:flex;gap:12px;">
            <div style="flex-shrink:0;margin-top:1px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:var(--cyan);">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <div class="text-strong" style="font-size:12px;margin-bottom:4px;">{{ __('How to connect a custom domain') }}</div>
                <div style="font-size:12px;color:var(--t3);line-height:1.7;">
                    1. {{ __('Add the domain here to get the required DNS records.') }}<br>
                    2. {{ __('Go to your domain registrar and add the DNS records shown after checking.') }}<br>
                    3. {{ __('Wait for DNS propagation (up to 48 h), then click "Check DNS" to verify.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── Edit domain modal ───────────────────────────────────────────── --}}
    @if($showEditModal)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);z-index:60;display:flex;align-items:center;justify-content:center;padding:18px;">
            <div class="card" style="width:min(100%,460px);border-radius:22px;padding:26px;border-color:var(--border2);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:22px;">
                    <div>
                        <div class="section-title">{{ __('Edit Domain') }}</div>
                        <div class="section-copy" style="margin-top:3px;">{{ __('Changing the domain will reset its status to Pending.') }}</div>
                    </div>
                    <button type="button" wire:click="$set('showEditModal', false)"
                        class="btn btn-secondary" style="padding:6px 10px;flex-shrink:0;">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="field-label">{{ __('Domain Name') }}</label>
                    <input type="text" wire:model="editDomain"
                           autocomplete="off" spellcheck="false"
                           class="field-control {{ $errors->has('editDomain') ? 'is-invalid' : '' }}">
                    @error('editDomain')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                    <p class="field-hint">{{ __('Use a subdomain (e.g. shop.yourbrand.com) or a root domain (yourbrand.com).') }}</p>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="button" wire:click="$set('showEditModal', false)"
                        class="btn btn-secondary" style="flex:1;justify-content:center;">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="updateDomain" wire:loading.attr="disabled"
                        class="btn btn-primary" style="flex:1;justify-content:center;">
                        <span wire:loading.remove wire:target="updateDomain">{{ __('Save Changes') }}</span>
                        <span wire:loading wire:target="updateDomain">{{ __('Saving…') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Add domain modal ────────────────────────────────────────────── --}}
    @if($showAddModal)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);z-index:60;display:flex;align-items:center;justify-content:center;padding:18px;">
            <div class="card" style="width:min(100%,460px);border-radius:22px;padding:26px;border-color:var(--border2);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:22px;">
                    <div>
                        <div class="section-title">{{ __('Add Custom Domain') }}</div>
                        <div class="section-copy" style="margin-top:3px;">{{ __('Enter your domain name to begin the connection process.') }}</div>
                    </div>
                    <button type="button" wire:click="$set('showAddModal', false)"
                        class="btn btn-secondary" style="padding:6px 10px;flex-shrink:0;">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <div style="margin-bottom:18px;">
                    <label class="field-label">{{ __('Domain Name') }}</label>
                    <input type="text" wire:model="newDomain"
                           placeholder="shop.yourbrand.com"
                           autocomplete="off" spellcheck="false"
                           class="field-control {{ $errors->has('newDomain') ? 'is-invalid' : '' }}">
                    @error('newDomain')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                    <p class="field-hint">{{ __('Use a subdomain (e.g. shop.yourbrand.com) or a root domain (yourbrand.com).') }}</p>
                </div>

                <div style="background:var(--elevated);border:1px solid var(--border1);border-radius:10px;padding:12px 14px;margin-bottom:18px;">
                    <div style="font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ __('What happens next') }}</div>
                    <div style="font-size:12px;color:var(--t3);display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;gap:8px;"><span style="color:var(--cyan);font-weight:600;">1.</span> {{ __('We create your domain request for review.') }}</div>
                        <div style="display:flex;gap:8px;"><span style="color:var(--cyan);font-weight:600;">2.</span> {{ __('You add the DNS records shown after checking.') }}</div>
                        <div style="display:flex;gap:8px;"><span style="color:var(--cyan);font-weight:600;">3.</span> {{ __('Once verified your domain goes live automatically.') }}</div>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="button" wire:click="$set('showAddModal', false)"
                        class="btn btn-secondary" style="flex:1;justify-content:center;">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="addDomain" wire:loading.attr="disabled"
                        class="btn btn-primary" style="flex:1;justify-content:center;">
                        <span wire:loading.remove wire:target="addDomain">{{ __('Submit Request') }}</span>
                        <span wire:loading wire:target="addDomain">{{ __('Submitting…') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</main>
