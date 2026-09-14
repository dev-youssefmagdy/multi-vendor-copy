@extends('tenant.layouts.app')

@section('title', $request['title'])

@section('content')
    <x-tenant::page-header :title="$request['title']" description="{{ 'Request #'.$request['id'].' · Submitted '.$request['created_at'] }}">
        <x-slot:meta>
            <span class="badge {{ $request['status_badge'] }}">{{ $request['status_label'] }}</span>
            @if(!empty($request['product_url']))
                <a href="{{ $request['product_url'] }}" target="_blank" rel="noopener"
                   class="badge badge-secondary" style="margin-top:8px;display:inline-flex;align-items:center;gap:5px;text-decoration:none;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    View Product Link
                </a>
            @endif
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('tenant.product-requests.index') }}" class="btn btn-secondary">&larr; All Requests</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @if($request['status'] !== 'rejected')
        <div class="card fu d1 t-request-progress">
            @foreach(['pending' => 'Submitted', 'reviewing' => 'Under Review', 'in_production' => 'In Production', 'completed' => 'Completed'] as $s => $label)
                @php $step = ['pending' => 1, 'reviewing' => 2, 'in_production' => 3, 'completed' => 4][$s]; @endphp
                <div class="t-request-progress-step {{ $request['status_step'] >= $step ? 'is-done' : '' }}">
                    <div class="t-request-progress-dot">{{ $step }}</div>
                    <span class="t-request-progress-label">{{ $label }}</span>
                    @if($step < 4)
                        <div class="t-request-progress-line {{ $request['status_step'] > $step ? 'is-done' : '' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="card fu d2" style="padding:24px;">
        <x-tenant::chat
            id="product-request-chat"
            empty="No messages yet."
            :messages="$request['messages']"
        >
            @if(!in_array($request['status'], ['completed', 'rejected']))
                <x-slot:composer>
                    <x-tenant::form
                        id="product-request-reply-form"
                        action="{{ route('tenant.product-requests.replies', $request['id']) }}"
                        validate="{{ route('tenant.product-requests.replies.validate', $request['id']) }}"
                        success="none"
                        files
                    >
                        <x-tenant::textarea name="reply" label="Add a message" rows="4" required placeholder="Type your message&hellip;" />
                        <x-tenant::dropzone
                            name="attachments"
                            label="Attach a file"
                            sublabel="Images, PDFs or documents, max 5 files"
                            accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx"
                            max-files="5"
                            max-kb="10240"
                        />
                        <div style="margin-top:12px;">
                            <button type="submit" class="btn btn-primary">Send Reply</button>
                        </div>
                    </x-tenant::form>
                </x-slot:composer>
            @endif
        </x-tenant::chat>
    </div>

    <script type="application/json" id="product-request-data">{"requestId": {{ $request['id'] }}}</script>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/product-request-show.js')
@endpush
