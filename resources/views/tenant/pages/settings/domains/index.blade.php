@extends('tenant.layouts.app')

@section('title', 'Domains')

@php
    $centralDomain = collect(config('tenancy.central_domains', []))->first(fn ($d) => !in_array($d, ['127.0.0.1', 'localhost']));
    $subdomains = $domains->filter(fn ($d) => $centralDomain && str_ends_with($d->domain, '.'.$centralDomain));
@endphp

@section('content')
    <x-tenant::page-header title="Domains" badge="Store" description="Manage your store domains and DNS connection status.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="add-domain-modal">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add Custom Domain
            </button>
        </x-slot:actions>
    </x-tenant::page-header>

    @if ($subdomains->isNotEmpty())
        <div class="card section-gap domains-card">
            <div class="table-header-shell domains-card-header">
                <div class="domains-card-heading">
                    <div class="si-box tone-bg-cyan">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-cyan">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="section-title">Subdomain</div>
                        <div class="section-copy">Your auto-generated store address — always active.</div>
                    </div>
                </div>
                <span class="chip c-g domains-chip">
                    <span class="dot dot-glow-green domains-dot"></span>
                    Active
                </span>
            </div>

            @foreach ($subdomains as $domain)
                <div class="domains-subdomain-row">
                    <div class="domains-subdomain-address">
                        <div class="domains-url-pill">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="domains-url-icon">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                            </svg>
                            <span class="text-strong domains-url-text">{{ $domain->domain }}</span>
                        </div>
                        <x-tenant::copy :value="'https://'.$domain->domain" label="Copy URL" />
                    </div>
                    <div class="domains-actions">
                        <a href="https://{{ $domain->domain }}" target="_blank" class="btn btn-secondary btn-sm">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                            Visit Store
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card domains-card">
        <div class="table-header-shell domains-card-header">
            <div class="domains-card-heading">
                <div class="si-box tone-bg-violet">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-violet">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <div>
                    <div class="section-title">Custom Domains</div>
                    <div class="section-copy">Point your own domain to this store.</div>
                </div>
            </div>
            @if ($domainRequests->isNotEmpty())
                <span class="domains-count">{{ $domainRequests->count() }} domain(s)</span>
            @endif
        </div>

        @forelse ($domainRequests as $req)
            @php
                $statusValue = $req->status->value ?? $req->status;
                $statusCfg = match ($statusValue) {
                    'connected' => ['c-g', 'dot-glow-green', 'Connected'],
                    'pending' => ['c-a', 'dot-amber', 'Pending Review'],
                    'removed' => ['', 'dot-muted', 'Removed'],
                    default => ['', 'dot-muted', $statusValue],
                };
                [$chipCls, $dotCls, $statusLabel] = $statusCfg;
                $isConnected = $statusValue === 'connected';
                $isPending = $statusValue === 'pending';
            @endphp

            <div class="domains-request-row" data-domain-request="{{ $req->id }}">
                <div class="domains-request-head">
                    <div class="domains-request-meta">
                        <div class="si-box {{ $isConnected ? 'tone-bg-green' : ($isPending ? 'tone-bg-amber' : 'domains-icon-plain') }}">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                 class="{{ $isConnected ? 'text-green' : ($isPending ? 'text-amber' : 'icon-t3') }}">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                            </svg>
                        </div>
                        <div class="domains-request-text">
                            <div class="text-strong domains-request-domain">{{ $req->domain }}</div>
                            <div class="td-date">Added {{ $req->requested_at?->diffForHumans() }}</div>
                        </div>
                    </div>

                    <span class="chip {{ $chipCls }} domains-chip">
                        <span class="dot {{ $dotCls }} domains-dot"></span>
                        {{ $statusLabel }}
                    </span>

                    <div class="domains-actions">
                        <button type="button" class="btn btn-secondary btn-sm" data-domain-check-dns
                            data-check-dns-url="{{ route('tenant.settings.domains.check-dns', $req) }}">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Check DNS
                        </button>

                        @if ($isConnected)
                            <a href="https://{{ $req->domain }}" target="_blank" class="btn btn-secondary btn-sm">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                                Visit
                            </a>
                        @endif

                        <button type="button" class="btn btn-secondary btn-sm"
                            data-modal-open="edit-domain-modal"
                            data-modal-fill-url="{{ route('tenant.settings.domains.show', $req) }}"
                            data-modal-action="{{ route('tenant.settings.domains.update', $req) }}"
                            data-modal-method="PUT"
                            data-modal-validate-url="{{ route('tenant.settings.domains.validate.update', $req) }}">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Edit
                        </button>

                        <button type="button" class="btn btn-secondary btn-sm domains-delete-btn"
                            data-action-url="{{ route('tenant.settings.domains.destroy', $req) }}"
                            data-action-method="DELETE"
                            data-confirm="Delete this domain? This cannot be undone."
                            data-confirm-danger
                            data-success="reload-page">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>

                <div class="domains-dns-panel" data-dns-panel hidden></div>
            </div>
        @empty
            <div class="empty-state">
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" class="domains-empty-icon">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <div class="empty-state-title">No custom domains yet</div>
                <div class="empty-state-copy">Add your own domain to give your store a branded URL.</div>
                <button type="button" class="btn btn-primary domains-empty-cta" data-modal-open="add-domain-modal">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Custom Domain
                </button>
            </div>
        @endforelse
    </div>

    <div class="card domains-guide-card">
        <div class="domains-guide-row">
            <div class="domains-guide-icon">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <div class="text-strong domains-guide-title">How to connect a custom domain</div>
                <div class="domains-guide-copy">
                    1. Add the domain here to get the required DNS records.<br>
                    2. Go to your domain registrar and add the DNS records shown after checking.<br>
                    3. Wait for DNS propagation (up to 48 h), then click "Check DNS" to verify.
                </div>
            </div>
        </div>
    </div>

    <x-tenant::modal id="add-domain-modal" title="Add Custom Domain" description="Enter your domain name to begin the connection process.">
        <x-tenant::form action="{{ route('tenant.settings.domains.store') }}" method="POST"
            validate="{{ route('tenant.settings.domains.validate') }}"
            success="close-modal reload-page">
            <div class="domains-modal-field">
                <label class="field-label">Domain Name</label>
                <x-tenant::input name="domain" placeholder="shop.yourbrand.com" autocomplete="off" spellcheck="false" />
                <p class="field-hint">Use a subdomain (e.g. shop.yourbrand.com) or a root domain (yourbrand.com).</p>
            </div>

            <div class="domains-next-steps">
                <div class="domains-next-steps-title">What happens next</div>
                <div class="domains-next-steps-list">
                    <div><span class="domains-step-num">1.</span> We create your domain request for review.</div>
                    <div><span class="domains-step-num">2.</span> You add the DNS records shown after checking.</div>
                    <div><span class="domains-step-num">3.</span> Once verified your domain goes live automatically.</div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary modal-actions-btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary modal-actions-btn">Submit Request</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>

    <x-tenant::modal id="edit-domain-modal" title="Edit Domain" description="Changing the domain will reset its status to Pending.">
        <x-tenant::form action="" method="PUT"
            success="close-modal reload-page">
            <div class="domains-modal-field">
                <label class="field-label">Domain Name</label>
                <x-tenant::input name="domain" autocomplete="off" spellcheck="false" />
                <p class="field-hint">Use a subdomain (e.g. shop.yourbrand.com) or a root domain (yourbrand.com).</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary modal-actions-btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary modal-actions-btn">Save Changes</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/domains.js')
@endpush
