<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Custom Template</h1>
                <span class="page-badge">Storefront</span>
            </div>
            <p class="page-copy">Upload your own HTML/CSS/JS storefront template instead of using a system theme.</p>
        </div>
    </div>

    <section class="card form-card fu d1 ct-card">
        <div class="gs-section-head">
            <h3 class="panel-title">Upload a Template ZIP</h3>
            <p class="panel-copy">
                The ZIP must contain an <code>index.html</code> file at its root. You may also
                include <code>assets/</code>, <code>css/</code>, <code>js/</code>, and
                <code>images/</code> folders. No other top-level folders or executable file
                types (<code>.php</code>, <code>.exe</code>, <code>.sh</code>, etc.) are
                allowed — uploads are validated and stripped of anything unsafe before being
                stored. Every upload is queued for admin review before it can go live.
            </p>
        </div>

        <div class="form-grid gs-grid">
            <div>
                <label class="field-label">Template ZIP</label>
                <input type="file" wire:model="templateZip" accept=".zip" class="ct-file-input" />
                @error('templateZip')<div class="field-error">{{ $message }}</div>@enderror
                <div wire:loading wire:target="templateZip" class="field-hint">Uploading…</div>
            </div>

            <div class="gs-actions">
                <x-btn type="button" wire:click="upload" wire:loading.attr="disabled" wire:target="upload">
                    Upload template
                </x-btn>
            </div>
        </div>
    </section>

    @if($previewVersion)
        <section class="card form-card fu d2 ct-card">
            <div class="gs-section-head" style="display:flex;align-items:center;justify-content:space-between;">
                <h3 class="panel-title">Preview — {{ $previewVersion }}</h3>
                <button type="button" wire:click="closePreview" class="ct-link">Close</button>
            </div>
            <iframe
                src="{{ route('tenant.store.custom-template.preview', ['version' => $previewVersion]) }}"
                sandbox="allow-scripts allow-same-origin"
                referrerpolicy="no-referrer"
                style="width:100%;height:600px;border:1px solid var(--border2);border-radius:12px;background:#fff;">
            </iframe>
        </section>
    @endif

    <section class="card form-card fu d2 ct-card">
        <div class="gs-section-head">
            <h3 class="panel-title">Uploaded Templates</h3>
            <p class="panel-copy">Only templates approved by our team can be activated on your storefront.</p>
        </div>

        @if($templates->isEmpty())
            <p class="panel-copy">No templates uploaded yet.</p>
        @else
            <div class="ct-table-wrap">
                <table class="ct-table">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Uploaded</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($templates as $template)
                            <tr>
                                <td>{{ $template->version }}</td>
                                <td>{{ $template->uploaded_at?->diffForHumans() ?? $template->created_at->diffForHumans() }}</td>
                                <td>
                                    <span class="ct-status ct-status-{{ $template->status }}">{{ ucfirst($template->status) }}</span>
                                    @if($template->status === 'rejected' && $template->rejection_reason)
                                        <div class="field-hint">{{ $template->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td>{{ $template->is_active ? 'Yes' : 'No' }}</td>
                                <td class="ct-actions">
                                    <button type="button" wire:click="preview('{{ $template->version }}')" class="ct-link">Preview</button>

                                    @if($template->status === 'approved' && !$template->is_active)
                                        <button type="button" wire:click="activate({{ $template->id }})" class="ct-link">Activate</button>
                                    @endif

                                    @if($template->is_active)
                                        <button type="button" wire:click="deactivate" class="ct-link">Deactivate</button>
                                    @endif

                                    @if(!$template->is_active)
                                        <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="ct-link ct-link-danger">Delete</button>
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
