<div>
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
                @else
                    <p style="font-size:13px;color:var(--t3);margin-top:6px">No current package assigned.</p>
                @endif
            </div>
            <div>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="open">
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

    <x-modal wire:model="showModal" title="Assign Package" maxWidth="2xl" closeAction="close">

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

        <div style="border-top:1px solid var(--border);margin:0 -20px 20px"></div>

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

        <div
            style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;padding-top:16px;border-top:1px solid var(--border)">
            <button type="button" class="btn btn-secondary" wire:click="close">Cancel</button>
            <x-btn type="button" wire:click="save">Assign &amp; Record Payment</x-btn>
        </div>
    </x-modal>
</div>
