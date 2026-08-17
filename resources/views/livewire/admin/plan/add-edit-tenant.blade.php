<main id="mn">
    <div class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Tenants</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage tenant identity, onboarding status, package assignment, and primary domain.
                </p>
            </div>
            <div class="page-actions" style="flex-shrink:0">
                <a href="{{ route('admin.plans.users') }}" class="btn btn-secondary">Back to Tenants</a>
                @if ($tenantId)
                    <a href="{{ route('admin.plans.users.impersonate', $tenantId) }}" class="btn btn-secondary"
                        title="Log in to this tenant's admin panel as the store owner">
                        Login to Store
                    </a>
                @endif
                @if ($activeTab === 'account')
                    <x-btn type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        x-on:click="$el.disabled = true">
                        <span wire:loading.remove wire:target="save">Save Tenant</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card section-gap notice-error">
                <h3 class="panel-title">Please fix the highlighted fields</h3>
                <p class="panel-copy">The form was not saved because some required values are missing or invalid.</p>
            </div>
        @endif

        {{-- ── Tabs ─────────────────────────────────────────────────────────── --}}
        <div class="section-tabs" style="margin-bottom:0">
            <button type="button" class="section-tab {{ $activeTab === 'account' ? 'is-active' : '' }}"
                wire:click="setTab('account')">
                <svg class="tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 20c0-3.866 3.582-7 8-7s8 3.134 8 7" />
                </svg>
                <div>
                    <span>Account</span>
                    <small>Identity &amp; settings</small>
                </div>
            </button>
            @if ($tenantId)
                <button type="button" class="section-tab {{ $activeTab === 'billing' ? 'is-active' : '' }}"
                    wire:click="setTab('billing')">
                    <svg class="tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                        <line x1="2" y1="10" x2="22" y2="10" />
                        <line x1="6" y1="15" x2="10" y2="15" />
                    </svg>
                    <div>
                        <span>Billing &amp; Plans</span>
                        <small>Subscriptions &amp; history</small>
                    </div>
                </button>
            @endif
        </div>

        {{-- ── Account Tab ──────────────────────────────────────────────────── --}}
        @if ($activeTab === 'account')
            <form wire:submit="save">
                <x-card-collapse title="Tenant Details"
                    subtitle="Identity, package assignment, and primary domain configuration." class="form-card"
                    :start-open="true">
                    <div class="form-grid form-grid-2">
                        <div>
                            <label class="field-label">Shop Name</label>
                            <x-input type="text" class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                                wire:model.defer="name" />
                            @error('name') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Slug</label>
                            <x-input type="text" class="{{ $errors->has('slug') ? 'is-invalid' : '' }}"
                                wire:model.defer="slug" />
                            @error('slug') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Owner Admin Email</label>
                            <x-input type="email" class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                wire:model.defer="email" />
                            @error('email') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Phone</label>
                            <x-input type="tel" data-phone-input class="{{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                wire:model.defer="phone" />
                            @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Status</label>
                            <x-select class="{{ $errors->has('status') ? 'is-invalid' : '' }}" wire:model.defer="status">
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                                @endforeach
                            </x-select>
                            @error('status') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Categories</label>
                            <p class="field-hint" style="margin-bottom:8px;">Select the root (parent) categories this tenant
                                can access. Leave empty for all categories.</p>
                            @if ($rootCategories->isEmpty())
                                <p class="field-hint" style="color:var(--warning);">No published root categories found.</p>
                            @else
                                <div
                                    style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
                                    @foreach ($rootCategories as $category)
                                        <label
                                            style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;background:{{ in_array((string) $category->id, $categoryIds) ? 'var(--primary-50,#fff7ed)' : 'transparent' }};">
                                            <input type="checkbox" wire:model.live="categoryIds" value="{{ $category->id }}"
                                                class="field-checkbox">
                                            <span
                                                style="font-size:13px;color:var(--t1);">{{ $category->translationValue('name') ?: $category->id }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            @error('categoryIds') <div class="field-error">{{ $message }}</div> @enderror
                            @error('categoryIds.*') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Primary Language</label>
                            <x-select class="{{ $errors->has('primaryLanguageId') ? 'is-invalid' : '' }}"
                                wire:model.defer="primaryLanguageId">
                                <option value="">No language</option>
                                @foreach ($languages as $language)
                                    <option value="{{ $language->id }}">{{ $language->name }}</option>
                                @endforeach
                            </x-select>
                            @error('primaryLanguageId') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Package</label>
                            <x-select class="{{ $errors->has('packageId') ? 'is-invalid' : '' }}"
                                wire:model.defer="packageId">
                                <option value="">No package</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                                @endforeach
                            </x-select>
                            @error('packageId') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Trial Ends At</label>
                            <x-input type="datetime-local" class="{{ $errors->has('trialEndsAt') ? 'is-invalid' : '' }}"
                                wire:model.defer="trialEndsAt" />
                            @error('trialEndsAt') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Profit Percentage (%)</label>
                            <x-input type="number" step="0.01" min="0" max="1000"
                                class="{{ $errors->has('profitPercentage') ? 'is-invalid' : '' }}"
                                wire:model.defer="profitPercentage" placeholder="0" />
                            <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Applied to central
                                product prices when syncing the catalog to this tenant.</p>
                            @error('profitPercentage') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">Primary Domain</label>
                            <x-input type="text" class="{{ $errors->has('domain') ? 'is-invalid' : '' }}"
                                wire:model.defer="domain" placeholder="store.example.com" />
                            @error('domain') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field-label">
                                {{ $tenantId ? 'New Password' : 'Password *' }}
                            </label>
                            <x-input type="password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                                wire:model.defer="password"
                                placeholder="{{ $tenantId ? 'Leave blank to keep current password' : 'Set the initial admin password' }}" />
                            @error('password') <div class="field-error">{{ $message }}</div> @enderror
                            @if ($tenantId)
                                <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Leave blank to keep
                                    the existing password unchanged.</p>
                            @endif
                        </div>

                        @if ($tenantId)
                            <div style="grid-column:1 / -1">
                                <label class="field-label">Logo</label>
                                <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
                                    <div style="flex-shrink:0">
                                        @if ($logoUpload)
                                            <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview"
                                                style="width:72px;height:72px;object-fit:contain;border-radius:8px;border:1px solid var(--border-color,#e5e7eb);background:#fff;">
                                        @elseif ($currentLogoUrl)
                                            <img src="{{ $currentLogoUrl }}" alt="{{ $name }}"
                                                style="width:72px;height:72px;object-fit:contain;border-radius:8px;border:1px solid var(--border-color,#e5e7eb);background:#fff;">
                                        @else
                                            <div
                                                style="width:72px;height:72px;border-radius:8px;border:1px solid var(--border-color,#e5e7eb);background:var(--surface-2,#f3f4f6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:26px;font-weight:700;color:var(--text-muted,#9ca3af);">
                                                {{ strtoupper(substr($name ?: '', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div style="flex:1;min-width:200px">
                                        <input type="file" accept="image/*" wire:model="logoUpload"
                                            class="field-control {{ $errors->has('logoUpload') ? 'is-invalid' : '' }}"
                                            style="padding:6px">
                                        @error('logoUpload') <div class="field-error">{{ $message }}</div> @enderror
                                        <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">JPG, PNG,
                                            GIF, or WebP · Max 2 MB. Leave blank to keep the existing logo.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </x-card-collapse>

                {{-- ── Bottom Actions ───────────────────────────────────────────── --}}
                <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
                    <a href="{{ route('admin.plans.users') }}" class="btn btn-secondary">Back to Tenants</a>
                    <x-btn type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        x-on:click="$el.disabled = true">
                        <span wire:loading.remove wire:target="save">Save Tenant</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </form>
        @endif

        {{-- ── Billing & Plans Tab ──────────────────────────────────────────── --}}
        @if ($activeTab === 'billing' && $tenantId)

            {{-- Current Plan --------------------------------------------------- --}}
            <div class="card form-card gap-2" style="padding:20px 24px">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                    <div>
                        <div class="panel-title" style="margin-bottom:4px">Current Plan</div>
                        @if ($currentPackage)
                            <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
                                <span style="font-size:15px;font-weight:600;color:var(--t1)">{{ $currentPackage->name }}</span>
                                <span style="font-size:13px;color:var(--t2)">
                                    @if ((float) $currentPackage->price === 0.0)
                                        Free
                                    @else
                                        {{ number_format((float) $currentPackage->price, 2) }}
                                    @endif
                                </span>
                            </div>
                            @if ($trialEndsAt)
                                <p style="font-size:12px;color:var(--t3);margin-top:6px">Trial ends:
                                    {{ \Carbon\Carbon::parse($trialEndsAt)->format('M d, Y') }}
                                </p>
                            @endif
                        @else
                            <p style="font-size:13px;color:var(--t3);margin-top:6px">No current package assigned.</p>
                        @endif
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="openAssignPackage">
                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5" style="margin-right:4px">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            {{ $currentPackage ? 'Change Package' : 'Add Package' }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Assign Package Modal ------------------------------------------- --}}
            <x-modal wire:model="showPackageModal" title="Assign Package" maxWidth="2xl" closeAction="closeAssignPackage">

                {{-- Plan Cards Grid --}}
                <div style="margin-bottom:20px">
                    <div
                        style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--t3);margin-bottom:12px">
                        Select a Plan
                    </div>
                    <div class="plan-select-grid">
                        @foreach ($packages as $package)
                            <div class="plan-select-card {{ (int) $assignPackageId === $package->id ? 'is-selected' : '' }}"
                                wire:click="$set('assignPackageId', {{ $package->id }})">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px">
                                    <span style="font-size:13px;font-weight:600;color:var(--t1)">{{ $package->name }}</span>
                                </div>
                                <div class="plan-price">
                                    @if ((float) $package->price === 0.0)
                                        Free
                                    @else
                                        ${{ number_format((float) $package->price, 2) }}
                                    @endif
                                </div>
                                @if ($package->trial_days)
                                    <div style="font-size:11px;color:var(--t3);margin-top:4px">
                                        {{ $package->trial_days }}-day trial
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('assignPackageId')
                        <p class="field-error" style="margin-top:8px">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Divider --}}
                <div style="border-top:1px solid var(--border);margin:0 -20px 20px"></div>

                {{-- Payment Details --}}
                <div
                    style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--t3);margin-bottom:12px">
                    Payment Details
                </div>
                <div class="form-grid form-grid-2" style="gap:14px">
                    <div>
                        <label class="field-label">Payment Method</label>
                        <x-select wire:model.live="assignGateway"
                            class="{{ $errors->has('assignGateway') ? 'is-invalid' : '' }}">
                            <option value="manual">Manual / Admin</option>
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </x-select>
                        @error('assignGateway') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Amount</label>
                        <x-input type="number" step="0.01" min="0" wire:model.defer="assignAmount"
                            class="{{ $errors->has('assignAmount') ? 'is-invalid' : '' }}"
                            placeholder="Auto-filled from plan" />
                        @error('assignAmount') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Payment Status</label>
                        <x-select wire:model.live="assignStatus"
                            class="{{ $errors->has('assignStatus') ? 'is-invalid' : '' }}">
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </x-select>
                        @error('assignStatus') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Reference / Transaction ID</label>
                        <x-input type="text" wire:model.defer="assignReference"
                            class="{{ $errors->has('assignReference') ? 'is-invalid' : '' }}" placeholder="Optional" />
                        @error('assignReference') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    @if ($assignStatus === 'paid')
                        <div>
                            <label class="field-label">Paid At</label>
                            <x-input type="datetime-local" wire:model.defer="assignPaidAt"
                                class="{{ $errors->has('assignPaidAt') ? 'is-invalid' : '' }}" />
                            @error('assignPaidAt') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                {{-- Modal Actions --}}
                <div
                    style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;padding-top:16px;border-top:1px solid var(--border)">
                    <button type="button" class="btn btn-secondary" wire:click="closeAssignPackage">Cancel</button>
                    <x-btn type="button" wire:click="saveAssignPackage">Assign &amp; Record Payment</x-btn>
                </div>
            </x-modal>

            {{-- Billing History ------------------------------------------------ --}}
            <div class="card form-card" style="padding:0;overflow:hidden">
                <div style="padding:16px 24px;border-bottom:1px solid var(--border)">
                    <div class="panel-title">Billing History</div>
                    <p class="panel-copy" style="margin-top:2px">All payment transactions for this tenant.</p>
                </div>

                @if ($billingHistory->isEmpty())
                    <div style="padding:40px 24px;text-align:center;color:var(--t3);font-size:13px">
                        No billing records found.
                    </div>
                @else
                    <div style="overflow-x:auto">
                        <table class="data-table" style="min-width:700px">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                    <th>Expires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($billingHistory as $log)
                                    @php
                                        $expireDate = null;
                                        if ($log->paid_at && $log->package) {
                                            $expireDate = match ($log->package->term?->value) {
                                                'monthly' => $log->paid_at->copy()->addMonth(),
                                                'quarterly' => $log->paid_at->copy()->addMonths(3),
                                                'yearly' => $log->paid_at->copy()->addYear(),
                                                default => null,
                                            };
                                        }
                                      @endphp
                                    <tr>
                                        <td>
                                            <span style="font-family:monospace;font-size:12px;color:var(--t2)">
                                                {{ $log->reference ? substr($log->reference, 0, 8) : '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($log->package)
                                                <span style="font-weight:500">{{ $log->package->name }}</span>
                                            @else
                                                <span style="color:var(--t3)">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ((float) $log->amount === 0.0)
                                                <span style="color:var(--t2)">Free</span>
                                            @else
                                                {{ number_format((float) $log->amount, 2) }}
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColor = match ($log->status->value) {
                                                    'paid' => 'var(--green, #22c55e)',
                                                    'failed' => 'var(--red, #ef4444)',
                                                    'refunded' => 'var(--orange, #f97316)',
                                                    default => 'var(--t3)',
                                                };
                                              @endphp
                                            <span style="font-size:12px;font-weight:600;color:{{ $statusColor }}">
                                                {{ $log->status->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color:var(--t2);font-size:13px">
                                                {{ $log->gateway ? ucfirst($log->gateway) : '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size:13px;color:var(--t2)">
                                                {{ $log->paid_at ? $log->paid_at->format('M d, Y') : ($log->created_at->format('M d, Y')) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size:13px;color:var(--t2)">
                                                {{ $expireDate ? $expireDate->format('M d, Y') : '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

    </div>
</main>