<div @if($tenants->count() <= 1) hidden @endif class="av" x-data="{ open: false }" style="position:relative;">
    @if($tenants->count() > 1)
        <button type="button" @click="open = !open" @click.outside="open = false"
                class="btn btn-secondary btn-sm xs-hide" style="display:flex;align-items:center;gap:6px;">
            <span>{{ $tenants->firstWhere('id', $selectedTenantId)?->name ?? __('Select store') }}</span>
            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>

        <div x-cloak x-show="open" x-transition
             style="position:absolute;top:calc(100% + 6px);right:0;min-width:220px;background:var(--surface,#171a23);border:1px solid var(--border,rgba(255,255,255,.08));border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.35);z-index:50;overflow:hidden;">
            @foreach($tenants as $tenant)
                <button type="button" wire:click="switchTenant('{{ $tenant->id }}')" @click="open = false"
                        style="display:block;width:100%;text-align:left;padding:9px 14px;background:{{ $tenant->id === $selectedTenantId ? 'var(--elevated)' : 'transparent' }};border:none;cursor:pointer;color:var(--t1);font-size:13px;">
                    {{ $tenant->name }}
                    @if($tenant->id === $selectedTenantId)
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="float:right;"><polyline points="20 6 9 17 4 12"/></svg>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
