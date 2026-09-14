@extends('tenant.layouts.app')

@section('title', 'AI Translation')

@section('content')
    <x-tenant::page-header title="AI Translation" badge="Settings"
        description="Purchase brand-aware AI translation for your store. Translations are tailored to your store name, products, and style." />

    @unless ($canUseAi)
        <div class="card section-gap notice-warning">
            AI translation is not enabled on your current plan. Upgrade to access this feature.
        </div>
    @else
        <div class="section-gap ai-translation-grid" data-ai-translation-cards
             data-status-url="{{ route('tenant.settings.ai-translation.status') }}">
            @forelse ($cards as $card)
                @include('tenant.pages.settings.ai-translation._card', ['card' => $card])
            @empty
                <div class="card fu d2" style="grid-column:1/-1">
                    <div class="empty-state">
                        <div class="empty-state-title">No AI translation available</div>
                        <p class="empty-state-copy">The platform admin has not configured AI translation pricing for any language yet.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <x-tenant::modal id="ai-translation-run-modal" title="Run AI Translation" size="sm">
            <div class="page-stack">
                <p class="panel-copy">
                    This will translate your entire store (products, categories, banners, UI strings) into
                    <strong data-ai-run-language-name>—</strong>, customized to your brand and product categories.
                </p>
                <div class="card" style="padding:12px 16px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;">
                    <span style="font-size:13px;color:var(--green)">This AI translation is included in your plan — no charge.</span>
                </div>
                <div class="page-actions compact-actions justify-end">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="button" class="btn btn-primary" id="ai-run-confirm-btn">Start AI Translation</button>
                </div>
            </div>
        </x-tenant::modal>

        <x-tenant::payment.gateway-modal
            id="ai-translation-purchase-modal"
            title="AI Translation — Buy"
            :action="route('tenant.settings.ai-translation.purchase', ['language' => 0])"
            :validate="route('tenant.settings.ai-translation.purchase.validate')"
            :presented="$presented"
            submit-label="Proceed to Payment"
        >
            <x-slot:summary>
                <div class="locale-fields-group" data-ai-purchase-summary style="margin-bottom:4px;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="flex:1;min-width:0;">
                            <div class="entity-title">
                                <span data-ai-purchase-summary-name>—</span>
                                <span class="badge badge-violet" data-ai-purchase-summary-code style="margin-left:6px;vertical-align:middle;"></span>
                            </div>
                            <div class="entity-subtitle" data-ai-purchase-summary-native>One-time payment</div>
                        </div>
                        <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;" data-ai-purchase-summary-price>$0.00</div>
                    </div>
                </div>
            </x-slot:summary>
        </x-tenant::payment.gateway-modal>

        <div class="card fu d2 table-card-shell section-gap">
            <div class="table-header-shell">
                <h3 class="panel-title">Translation History</h3>
            </div>
            <x-tenant::datatable id="ai-translation-history-table" :url="route('tenant.settings.ai-translation.history.data')" :columns="$columns"
                empty-title="No translation history yet" empty-copy="Run or purchase an AI translation to see it appear here."
                :order="[[0, 'desc']]" />
        </div>
    @endunless
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/ai-translation.js')
@endpush
