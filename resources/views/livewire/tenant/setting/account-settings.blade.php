<main id="mn">

    {{-- ══════════════════════════════════════════════════════════════════════
         Page header
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Account Settings</h1>
                <span class="page-badge">Profile</span>
            </div>
            <p class="page-copy">Manage your admin credentials and store identity.</p>
        </div>
        <div class="page-actions">
            <x-btn type="submit" form="account-settings-form">Save Changes</x-btn>
        </div>
    </div>

    <form id="account-settings-form" wire:submit="save" class="acct-layout">

        {{-- ══════════════════════════════════════════════════════════════════
             Left column — Avatar card
        ══════════════════════════════════════════════════════════════════ --}}
        <aside class="acct-sidebar">

            <div class="card acct-avatar-card fu d1">
                <div class="acct-avatar-wrap">
                    <div class="acct-avatar">
                        <span class="acct-avatar-initials">
                            {{ strtoupper(substr($adminName ?: 'A', 0, 1)) }}{{ strtoupper(substr(strstr($adminName ?: '', ' ') ?: '', 1, 1)) }}
                        </span>
                    </div>
                </div>
                <div class="acct-avatar-meta">
                    <div class="acct-avatar-name D">{{ $adminName ?: 'Admin' }}</div>
                    <div class="acct-avatar-email">{{ $adminEmail }}</div>
                    <span class="page-badge" style="margin-top:8px;">Store Admin</span>
                </div>
                <div class="acct-info-list">
                    <div class="acct-info-row">
                        <svg class="acct-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="acct-info-text">{{ $shopName ?: '—' }}</span>
                    </div>
                    @if ($phone)
                        <div class="acct-info-row">
                            <svg class="acct-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="acct-info-text">{{ $phone }}</span>
                        </div>
                    @endif
                </div>
            </div>

        </aside>

        {{-- ══════════════════════════════════════════════════════════════════
             Right column — Form sections
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="acct-form-col">

            {{-- Identity section --}}
            <section class="card form-card fu d2 section-gap">
                <div class="acct-section-head">
                    <div class="acct-section-icon-wrap">
                        <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="panel-title">Profile &amp; Store Identity</h3>
                        <p class="panel-copy">Your admin credentials and storefront-facing details.</p>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">Admin Name</label>
                        <x-input type="text" wire:model.defer="adminName" :error="$errors->has('adminName')" />
                        @error('adminName')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Admin Email</label>
                        <x-input type="email" wire:model.defer="adminEmail" :error="$errors->has('adminEmail')" />
                        @error('adminEmail')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Store Phone</label>
                        <x-input type="text" wire:model.defer="phone" :error="$errors->has('phone')" />
                        @error('phone')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Shop Name</label>
                        <x-input type="text" wire:model.defer="shopName" :error="$errors->has('shopName')" />
                        @error('shopName')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- Store details section --}}
            <section class="card form-card fu d3 section-gap" id="store-details">
                <div class="acct-section-head">
                    <div class="acct-section-icon-wrap">
                        <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 21h18M5 21V7l8-4 8 4v14M9 21v-6h6v6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="panel-title">Store Details</h3>
                        <p class="panel-copy">Details shown to customers on your storefront.</p>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <div class="acct-span-full">
                        <label class="field-label">Store Description</label>
                        <x-input type="text" wire:model.defer="description" :error="$errors->has('description')" />
                        @error('description')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="acct-span-full">
                        <label class="field-label">Address</label>
                        <x-input type="text" wire:model.defer="address" :error="$errors->has('address')" />
                        @error('address')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            {{-- Security section --}}
            <section class="card form-card fu d3">
                <div class="acct-section-head">
                    <div class="acct-section-icon-wrap">
                        <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="panel-title">Password &amp; Security</h3>
                        <p class="panel-copy">Leave blank to keep your current password.</p>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <div class="acct-span-full">
                        <label class="field-label">New Password</label>
                        <x-input type="password" wire:model.defer="password" :error="$errors->has('password')" />
                        @error('password')<div class="field-error">{{ $message }}</div>@enderror
                        <p class="acct-hint">Minimum 6 characters. Leave empty to keep your current password.</p>
                    </div>
                </div>
            </section>

            {{-- Mobile save button --}}
            <div class="acct-mobile-save">
                <x-btn type="submit" form="account-settings-form">Save Changes</x-btn>
            </div>

        </div>

    </form>

</main>

<style>
/* ── Account Settings Layout ─────────────────────────────────────────────── */
.acct-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 16px;
    align-items: start;
}

.acct-sidebar {
    position: sticky;
    top: 76px;
}

.acct-avatar-card {
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
    text-align: center;
}

.acct-avatar-wrap {
    margin-bottom: 14px;
}

.acct-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(0,229,255,0.22), rgba(162,89,255,0.22));
    border: 2px solid rgba(0,229,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.acct-avatar-initials {
    font-size: 22px;
    font-weight: 800;
    color: var(--cyan);
    letter-spacing: 0.03em;
    line-height: 1;
}

.acct-avatar-meta {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin-bottom: 16px;
}

.acct-avatar-name {
    font-size: 15px;
    font-weight: 700;
}

.acct-avatar-email {
    font-size: 12px;
    color: var(--t3);
    word-break: break-all;
}

.acct-info-list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 14px;
    border-top: 1px solid var(--border);
    text-align: left;
}

.acct-info-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.acct-info-icon {
    width: 14px;
    height: 14px;
    color: var(--t3);
    flex-shrink: 0;
    margin-top: 2px;
}

.acct-info-text {
    font-size: 12.5px;
    color: var(--t2);
    line-height: 1.4;
    word-break: break-word;
}

.acct-form-col {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.acct-section-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.acct-section-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(0,229,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.acct-section-icon {
    width: 18px;
    height: 18px;
    color: var(--cyan);
}

.acct-span-full {
    grid-column: 1 / -1;
}

.acct-hint {
    font-size: 11.5px;
    color: var(--t3);
    margin-top: 5px;
    line-height: 1.4;
}

.acct-mobile-save {
    display: none;
}

/* ── Tablet (≤ 1023px) ───────────────────────────────────────────────────── */
@media (max-width: 1023px) {
    .acct-layout {
        grid-template-columns: 1fr;
    }

    .acct-sidebar {
        position: static;
    }

    .acct-avatar-card {
        flex-direction: row;
        text-align: left;
        align-items: center;
        gap: 16px;
        padding: 18px 20px;
    }

    .acct-avatar-wrap {
        margin-bottom: 0;
        flex-shrink: 0;
    }

    .acct-avatar {
        width: 56px;
        height: 56px;
    }

    .acct-avatar-initials {
        font-size: 18px;
    }

    .acct-avatar-meta {
        flex: 1;
        align-items: flex-start;
        gap: 3px;
        margin-bottom: 0;
    }

    .acct-info-list {
        display: none;
    }
}

/* ── Mobile (≤ 639px) ────────────────────────────────────────────────────── */
@media (max-width: 639px) {
    .acct-avatar-card {
        padding: 14px 16px;
    }

    .acct-avatar {
        width: 48px;
        height: 48px;
    }

    .acct-avatar-initials {
        font-size: 16px;
    }

    .acct-avatar-name {
        font-size: 14px;
    }

    .page-actions {
        display: none;
    }

    .acct-mobile-save {
        display: flex;
        justify-content: flex-end;
        padding-top: 4px;
    }
}
</style>
