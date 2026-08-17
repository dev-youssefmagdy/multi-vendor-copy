@php
    $data = fn(string $key, $default = '—') => filled(data_get($tenant, "data.{$key}")) ? data_get($tenant, "data.{$key}") : $default;
    $doc = fn(string $key) => data_get($tenant, "data.{$key}");
    $status = (string) ($tenant?->compliance_status ?: 'pending');
    $canManage = $this->hasPermission('compliance.tenants.manage');
@endphp

<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                <span class="page-badge">{{ $badge }}</span>
                <span class="badge {{ $status === 'verified' ? 'badge-green' : ($status === 'needs_action' ? 'badge-red' : 'badge-amber') }}">
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </span>
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('admin.compliance.index') }}">← Compliance Overview</a>
        </div>
    </div>

    @if (!$tenant)
        <div class="card form-card">Tenant not found.</div>
    @else
        <div class="page-stack section-gap">

            <section class="card form-card fu d1">
                <div class="acct-section-head">
                    <div>
                        <h3 class="panel-title">Completion — {{ $completionPercent }}%</h3>
                        <p class="panel-copy">Weighted across required Compliance Center fields.</p>
                    </div>
                </div>
                <div style="height:8px;border-radius:999px;background:var(--elevated);overflow:hidden;">
                    <div style="height:100%;width:{{ $completionPercent }}%;background:{{ $completionPercent >= 100 ? 'var(--green)' : 'var(--cyan)' }};"></div>
                </div>
            </section>

            <section class="card form-card fu d2">
                <h3 class="panel-title">Business Info</h3>
                <div class="form-grid form-grid-2">
                    <div><span class="field-label">Business Name</span><div>{{ $data('compliance_business_name') }}</div></div>
                    <div><span class="field-label">Store Name</span><div>{{ $data('compliance_store_name') }}</div></div>
                    <div><span class="field-label">City</span><div>{{ $data('compliance_city') }}</div></div>
                    <div><span class="field-label">Phone</span><div>{{ $data('compliance_phone') }}</div></div>
                    <div><span class="field-label">Email</span><div>{{ $data('compliance_email') }}</div></div>
                </div>
            </section>

            <section class="card form-card fu d3">
                <h3 class="panel-title">Owner Info</h3>
                <div class="form-grid form-grid-2">
                    <div><span class="field-label">Owner Name</span><div>{{ $data('compliance_owner_name') }}</div></div>
                    <div><span class="field-label">ID / Passport Number</span><div>{{ $data('compliance_owner_id_number') }}</div></div>
                    <div><span class="field-label">Date of Birth</span><div>{{ $data('compliance_owner_dob') }}</div></div>
                    <div><span class="field-label">Contact</span><div>{{ $data('compliance_owner_contact') }}</div></div>
                </div>
            </section>

            <section class="card form-card fu d4">
                <h3 class="panel-title">Company Info</h3>
                <div class="form-grid form-grid-2">
                    <div><span class="field-label">Company Name</span><div>{{ $data('compliance_company_name') }}</div></div>
                    <div><span class="field-label">Registration Number</span><div>{{ $data('compliance_registration_number') }}</div></div>
                    <div><span class="field-label">Registration Expiry</span><div>{{ $data('compliance_registration_expiry') }}</div></div>
                    <div><span class="field-label">VAT / Tax Number</span><div>{{ $data('compliance_vat_number') }}</div></div>
                    <div class="acct-span-full">
                        <span class="field-label">Registration Document</span>
                        @if ($doc('compliance_registration_document_path'))
                            <a href="{{ $doc('compliance_registration_document_path') }}" target="_blank" class="btn btn-secondary btn-sm">View document</a>
                        @else
                            <div>—</div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="card form-card fu d5">
                <h3 class="panel-title">Bank Account</h3>
                <div class="form-grid form-grid-2">
                    <div><span class="field-label">Bank Name</span><div>{{ $data('compliance_bank_name') }}</div></div>
                    <div><span class="field-label">Account Holder</span><div>{{ $data('compliance_bank_holder_name') }}</div></div>
                    <div><span class="field-label">IBAN</span><div>{{ $data('compliance_bank_iban') }}</div></div>
                    <div><span class="field-label">Account Number</span><div>{{ $data('compliance_bank_account_number') }}</div></div>
                    <div><span class="field-label">Currency</span><div>{{ $data('compliance_bank_currency') }}</div></div>
                </div>
            </section>

            <section class="card form-card fu d6">
                <h3 class="panel-title">Verification Documents</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'compliance_doc_national_id_path' => 'National ID',
                        'compliance_doc_commercial_registration_path' => 'Commercial Registration',
                        'compliance_doc_tax_certificate_path' => 'Tax Certificate',
                    ] as $key => $label)
                        @if ($doc($key))
                            <a href="{{ $doc($key) }}" target="_blank" class="btn btn-secondary btn-sm">{{ $label }}</a>
                        @endif
                    @endforeach
                    @foreach ((array) $doc('compliance_doc_additional_paths') as $i => $path)
                        <a href="{{ $path }}" target="_blank" class="btn btn-secondary btn-sm">Additional Document {{ $i + 1 }}</a>
                    @endforeach
                    @if (!$doc('compliance_doc_national_id_path') && !$doc('compliance_doc_commercial_registration_path') && !$doc('compliance_doc_tax_certificate_path') && empty($doc('compliance_doc_additional_paths')))
                        <span class="entity-subtitle">No documents uploaded yet.</span>
                    @endif
                </div>
            </section>

            @if ($canManage)
                <section class="card form-card fu d7">
                    <h3 class="panel-title">Admin Review</h3>
                    <div class="form-grid">
                        <div class="acct-span-full">
                            <label class="field-label">Internal Note</label>
                            <textarea class="input" rows="3" wire:model.defer="note" placeholder="Reason for the current status, missing documents, etc."></textarea>
                        </div>
                    </div>
                    <div class="page-actions" style="margin-top:14px;">
                        <button type="button" class="btn btn-primary" wire:click="markVerified">Mark as Verified</button>
                        <button type="button" class="btn btn-secondary" wire:click="markNeedsAction">Mark as Needs Action</button>
                    </div>
                    @if ($tenant->compliance_reviewed_at)
                        <p class="entity-subtitle" style="margin-top:10px;">
                            Last reviewed by {{ $tenant->compliance_reviewed_by ?? '-' }} on {{ $tenant->compliance_reviewed_at->format('M d, Y H:i') }}
                        </p>
                    @endif
                </section>
            @endif

        </div>
    @endif
</main>
