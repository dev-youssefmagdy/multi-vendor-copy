{{-- Shared flash-sale picker modal used by opportunity card buttons --}}
<div id="opp-flash-sale-modal" class="modal-shell t-modal fixed inset-0 z-50 overflow-y-auto" data-tenant-modal hidden role="dialog" aria-modal="true" aria-labelledby="opp-flash-sale-modal-title">
    <div class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-[3px]"></div>
    <div class="flex min-h-full items-center justify-center px-4 py-6 sm:px-0">
        <div class="card w-full sm:max-w-md relative z-10 p-0 modal-card">
            <div class="p-5 border-b flex justify-between items-center modal-header-shell">
                <div>
                    <h3 class="panel-title modal-title" id="opp-flash-sale-modal-title">Add to Flash Sale</h3>
                    <p class="panel-copy">Select a flash sale to add this product to.</p>
                </div>
                <button type="button" data-modal-close class="modal-close-btn transition-colors" aria-label="Close">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="p-5 modal-body-shell" id="opp-flash-sale-modal-body">
                <p style="color:var(--t3);font-size:14px;">Loading flash sales…</p>
            </div>
        </div>
    </div>
</div>
