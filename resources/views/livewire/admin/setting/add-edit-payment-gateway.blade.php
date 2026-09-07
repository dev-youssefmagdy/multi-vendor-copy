<main id="mn">
    <form wire:submit="save" class="page-stack">

        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Payment Gateways</div>
                <h1 class="D page-title">{{ $name ?: 'Edit Gateway' }}</h1>
                <p class="page-copy">Manage gateway credentials, display name, and activation status.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.settings.payment-gateways') }}" class="btn btn-secondary">Back to
                    Gateways</a>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Connect &amp; Save</span>
                    <span wire:loading wire:target="save">Connecting…</span>
                </x-btn>
            </div>
        </div>

        <div class="grid gap-2">

            {{-- Logo --}}
            <x-card-collapse class="span-4" title="Gateway Logo"
                subtitle="Square image displayed in checkout and settings views." :start-open="true">
                <div class="pg-logo-section">
                    @if ($logoImage)
                        {{-- New upload live preview --}}
                        <div class="pg-logo-preview-wrap">
                            <img src="{{ $logoImage->temporaryUrl() }}" alt="New logo" class="pg-logo-preview-img">
                            <button type="button" class="pg-logo-remove-btn" wire:click="$set('logoImage', null)"
                                title="Cancel new upload">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>
                    @elseif ($existingLogoUrl && !$removeLogo)
                        {{-- Existing stored logo --}}
                        <div class="pg-logo-preview-wrap">
                            <img src="{{ $existingLogoUrl }}" alt="Current logo" class="pg-logo-preview-img">
                            <button type="button" class="pg-logo-remove-btn" wire:click="removeLogoImage"
                                title="Remove logo">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if (!$logoImage && (!$existingLogoUrl || $removeLogo))
                        <x-dropzone model="logoImage" :multiple="false" label="Upload gateway logo"
                            sublabel="PNG, JPG, WEBP up to 2MB — scaled to 400×400" accept="image/*" />
                    @endif

                    @error('logoImage')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
            </x-card-collapse>

            {{-- General Settings --}}
            <x-card-collapse class="span-8" title="General Settings" subtitle="Name, code, status, and mode."
                :start-open="true">
                <div class="form-grid">

                    <div class="span-2">
                        <label class="field-label">Name</label>
                        <x-input type="text" wire:model.defer="name" />
                        @error('name')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="field-label">Code
                            <span class="pg-readonly-badge">read-only</span>
                        </label>
                        <x-input type="text" :value="$code" disabled />
                    </div>

                    <div>
                        <label class="field-label">Mode</label>
                        <label class="pg-toggle-row">
                            <input type="checkbox" wire:model.live="sandboxMode" />
                            <span>{{ $sandboxMode ? 'Sandbox / Test mode' : 'Live mode' }}</span>
                        </label>
                    </div>

                    <div class="span-2">
                        <label class="field-label">Status</label>
                        <x-select wire:model.defer="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('status')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>

                </div>
            </x-card-collapse>

            {{-- Transaction Fees --}}
            <x-card-collapse class="span-12" title="Transaction Fees"
                subtitle="Fees this gateway charges per transaction (applied when vendors pay central for product fulfillment)."
                :start-open="true">
                <div class="form-grid">
                    <div>
                        <label class="field-label">Percentage Fee <span class="pg-readonly-badge">e.g. 3.75 =
                                3.75%</span></label>
                        <x-input type="number" wire:model.defer="feePercentage" step="0.0001" min="0" max="100"
                            placeholder="0.0000" />
                        @error('feePercentage')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Fixed Fee <span class="pg-readonly-badge">e.g. 0.50 =
                                $0.50</span></label>
                        <x-input type="number" wire:model.defer="feeFixed" step="0.01" min="0" placeholder="0.00" />
                        @error('feeFixed')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="span-2">
                        <p class="text-xs text-gray-400 mt-1">
                            Total fee per transaction = Fixed + (Percentage ÷ 100) × amount.
                            Example: $0.50 fixed + 3.75% on a $100 order = <strong>$4.25</strong>.
                        </p>
                    </div>
                </div>
            </x-card-collapse>

            {{-- Credentials --}}
            <x-card-collapse class="span-12" title="Credentials"
                subtitle="Gateway API keys and secret values stored encrypted in the database." :start-open="true">

                @error('credentials')<div class="text-red-500 text-sm mb-3">{{ $message }}</div>@enderror

                @if (empty($credentials))
                    <div class="empty-state" style="padding:24px 0;">
                        <div class="empty-state-title">No credentials defined</div>
                        <p class="empty-state-copy">This gateway has no required credential keys configured in its strategy
                            class.</p>
                    </div>
                @else
                    <div class="pg-credentials-list">
                        @foreach ($credentials as $index => $cred)
                            <div class="pg-credential-row">
                                <div class="pg-credential-label-col">
                                    <span class="pg-credential-key">{{ str($cred['key'])->headline() }}</span>
                                    <code class="pg-credential-code">{{ $cred['key'] }}</code>
                                </div>
                                <div class="pg-credential-input-col">
                                    <x-input type="password" wire:model.defer="credentials.{{ $index }}.value"
                                        placeholder="Enter {{ str($cred['key'])->headline()->lower() }}" autocomplete="off" />
                                    @error("credentials.{$index}.value")
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </x-card-collapse>

        </div>
    </form>
</main>

@push('styles')
    <style>
        .pg-readonly-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 500;
            color: var(--t3);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 1px 6px;
            margin-left: 6px;
            vertical-align: middle;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .pg-toggle-row {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            font-size: 13px;
            color: var(--t1);
        }

        .pg-toggle-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .pg-credentials-list {
            display: flex;
            flex-direction: column;
            gap: 0;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .pg-credential-row {
            display: grid;
            grid-template-columns: 220px 1fr;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        .pg-credential-row:last-child {
            border-bottom: none;
        }

        .pg-credential-row:nth-child(even) {
            background: var(--surface);
        }

        .pg-credential-label-col {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .pg-credential-key {
            font-size: 13px;
            font-weight: 600;
            color: var(--t1);
        }

        .pg-credential-code {
            font-size: 11px;
            color: var(--t3);
            font-family: monospace;
            background: none;
            padding: 0;
        }

        .pg-credential-input-col {
            flex: 1;
        }

        @media (max-width: 600px) {
            .pg-credential-row {
                grid-template-columns: 1fr;
            }
        }

        /* Logo upload */
        .pg-logo-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pg-logo-preview-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .pg-logo-preview-img {
            max-width: 140px;
            max-height: 140px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            display: block;
        }

        .pg-logo-remove-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: rgba(0, 0, 0, .45);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
        }

        .pg-logo-remove-btn:hover {
            background: rgba(220, 38, 38, .85);
        }
    </style>
@endpush