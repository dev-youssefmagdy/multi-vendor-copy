<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ __('Blade Theme') }}</h1>
                <span class="page-badge">{{ __('Storefront') }}</span>
            </div>
            <p class="page-copy">{{ __('Upload your own Blade storefront theme instead of using a system theme.') }}</p>
        </div>
    </div>

    <div class="card section-gap" style="padding:16px;display:flex;align-items:center;justify-content:space-between;gap:16px">
        <div>
            <h3 class="panel-title" style="font-size:14px;margin-bottom:4px">{{ __('New to Blade themes?') }}</h3>
            <p class="panel-copy" style="font-size:13px">
                {{ __('Download the starter kit — a working theme with header, footer, and all home sections wired to real store data — and customize it as your own.') }}
            </p>
        </div>
        <a href="{{ route('tenant.store.blade-theme.starter-kit') }}" class="btn btn-secondary" style="flex-shrink:0">
            {{ __('Download Starter Kit') }}
        </a>
    </div>

    <section class="card form-card fu d1 ct-card">
        <div class="gs-section-head">
            <h3 class="panel-title">{{ __('Upload a Theme ZIP') }}</h3>
            <p class="panel-copy">
                {{ __('The ZIP must contain') }} <code>layout/app.blade.php</code> {{ __('and') }}
                <code>pages/home/index.blade.php</code>{{ __('. Blade (') }}<code>.blade.php</code>{{ __('), CSS, JS, and common image/font files are allowed. Obvious PHP-execution patterns are rejected up front, but this is only a first pass — every upload is queued for admin review, and your theme cannot go live on your storefront until it is approved.') }}
            </p>
        </div>

        <div class="form-grid gs-grid">
            <div>
                <label class="field-label">{{ __('Theme ZIP') }}</label>
                <input type="file" wire:model="themeZip" accept=".zip" class="ct-file-input" />
                @error('themeZip')<div class="field-error">{{ $message }}</div>@enderror
                <div wire:loading wire:target="themeZip" class="field-hint">{{ __('Uploading…') }}</div>
            </div>

            <div class="gs-actions">
                <x-btn type="button" wire:click="submitUpload" wire:loading.attr="disabled" wire:target="submitUpload">
                    {{ __('Upload theme') }}
                </x-btn>
            </div>
        </div>
    </section>

    <section class="card form-card fu d2 ct-card">
        <div class="gs-section-head">
            <h3 class="panel-title">{{ __('Uploaded Themes') }}</h3>
            <p class="panel-copy">{{ __('Approved themes are activated from Storefront → Online Store → Themes, alongside your other storefront theme options.') }}</p>
        </div>

        @if($themes->isEmpty())
            <p class="panel-copy">{{ __('No themes uploaded yet.') }}</p>
        @else
            <div class="ct-table-wrap">
                <table class="ct-table">
                    <thead>
                        <tr>
                            <th>{{ __('Version') }}</th>
                            <th>{{ __('Uploaded') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($themes as $theme)
                            <tr>
                                <td>{{ $theme->version }}</td>
                                <td>{{ $theme->uploaded_at?->diffForHumans() ?? $theme->created_at->diffForHumans() }}</td>
                                <td>
                                    <span class="ct-status ct-status-{{ $theme->status }}">{{ __(ucfirst($theme->status)) }}</span>
                                    @if($theme->status === 'rejected' && $theme->rejection_reason)
                                        <div class="field-hint">{{ $theme->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td>{{ $theme->is_active ? __('Yes') : __('No') }}</td>
                                <td class="ct-actions">
                                    @if($theme->is_active)
                                        <button type="button" wire:click="deactivate" class="ct-link">{{ __('Deactivate') }}</button>
                                    @endif

                                    @if(!$theme->is_active)
                                        <button type="button" wire:click="delete({{ $theme->id }})" wire:confirm="{{ __('Delete this theme?') }}" class="ct-link ct-link-danger">{{ __('Delete') }}</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</main>

<style>
.ct-card { max-width: 900px; }
.ct-file-input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--border2);
    background: rgba(255,255,255,0.02);
    color: var(--t1);
}
.ct-table-wrap { overflow-x: auto; }
.ct-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ct-table th, .ct-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border2); }
.ct-status { padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.ct-status-pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
.ct-status-approved { background: rgba(34,197,94,0.15); color: #22c55e; }
.ct-status-rejected { background: rgba(239,68,68,0.15); color: #ef4444; }
.ct-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.ct-link { background: none; border: none; color: var(--primary, #6366f1); cursor: pointer; font-size: 13px; padding: 0; }
.ct-link-danger { color: #ef4444; }
</style>
