<main id="mn" wire:key="product-request-{{ $request['id'] ?? 0 }}">
  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">{{ $request['title'] }}</h1>
        <span class="badge {{ $request['status_badge'] }}">{{ $request['status_label'] }}</span>
      </div>
      <p class="page-copy">Request #{{ $request['id'] }} from tenant <strong>{{ $request['tenant_id'] }}</strong> &middot; {{ $request['created_at'] }}</p>
    </div>
    <a href="{{ route('admin.product-requests.index') }}" class="btn btn-secondary">&larr; All Requests</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    <div class="stack-gap">

      @if (!empty($request['attachments']))
        <div class="card fu d1" style="padding:16px 20px;">
          <p class="panel-title" style="margin-bottom:8px;">Request Attachments</p>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            @foreach ($request['attachments'] as $path)
              <a href="{{ asset('storage/' . $path) }}" target="_blank" class="badge badge-secondary">&#128206; {{ basename($path) }}</a>
            @endforeach
          </div>
        </div>
      @endif

      <div class="card fu d2" style="padding:24px;">
        <div class="stack-gap" style="gap:16px;" id="product-request-thread">
          @foreach ($request['messages'] as $msg)
            <div style="display:flex;{{ $msg['is_mine'] ? 'justify-content:flex-end' : 'justify-content:flex-start' }}">
              <div style="max-width:78%;padding:14px 16px;border-radius:{{ $msg['is_mine'] ? '14px 4px 14px 14px' : '4px 14px 14px 14px' }};
                          background:{{ $msg['is_mine'] ? 'var(--cyan)' : 'var(--card2)' }};
                          color:{{ $msg['is_mine'] ? '#060c18' : 'var(--t1)' }};">
                <div style="font-size:11px;font-weight:700;margin-bottom:5px;opacity:0.7;">
                  {{ $msg['sender_name'] }} &middot; {{ $msg['sent_at'] }}
                </div>
                <div style="font-size:13.5px;line-height:1.55;white-space:pre-wrap;">{{ $msg['body'] }}</div>
                @if (!empty($msg['attachments']))
                  <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:5px;">
                    @foreach ($msg['attachments'] as $path)
                      <a href="{{ asset('storage/' . $path) }}" target="_blank"
                         style="font-size:11px;padding:3px 8px;border-radius:6px;background:rgba(0,0,0,0.15);text-decoration:none;color:inherit;">
                        &#128206; {{ basename($path) }}
                      </a>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="card fu d3" style="padding:20px 24px;">
        <label class="field-label">Reply to tenant</label>
        <textarea class="field-control" rows="4" wire:model.defer="reply" placeholder="Type your reply&hellip;"></textarea>
        @error('reply') <p class="field-error">{{ $message }}</p> @enderror
        <div style="margin-top:10px;">
          <input type="file" multiple wire:model="replyFiles" class="field-control"
                 accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx">
        </div>
        <button type="button" class="btn btn-primary" style="margin-top:12px;" wire:click="sendReply">
          <span wire:loading.remove wire:target="sendReply">Send Reply</span>
          <span wire:loading wire:target="sendReply">Sending&hellip;</span>
        </button>
      </div>

    </div>

    <div class="stack-gap">
      <div class="card fu d4" style="padding:20px 22px;">
        <h3 class="panel-title" style="margin-bottom:14px;">Request Status</h3>

        <div style="margin-bottom:14px;">
          <label class="field-label">Status</label>
          <x-select wire:model.defer="statusSelection">
            @foreach ($statusOptions as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
          </x-select>
        </div>

        <div style="margin-bottom:16px;">
          <label class="field-label">Priority</label>
          <x-select wire:model.defer="priority">
            @foreach ($priorityOptions as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
          </x-select>
        </div>

        <button type="button" class="btn btn-primary w-full" wire:click="updateStatus">
          Save Status
        </button>
      </div>

      @if ($request['status'] !== 'rejected')
      <div class="card fu d5" style="padding:16px 20px;">
        <h3 class="panel-title" style="margin-bottom:12px;">Progress</h3>
        @foreach (['pending' => 'Submitted', 'reviewing' => 'Under Review', 'in_production' => 'In Production', 'completed' => 'Completed'] as $s => $label)
          @php $step = ['pending' => 1, 'reviewing' => 2, 'in_production' => 3, 'completed' => 4][$s]; @endphp
          <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--border);">
            <div style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;
                        background:{{ $request['status_step'] >= $step ? 'var(--cyan)' : 'var(--border)' }};
                        color:{{ $request['status_step'] >= $step ? '#060c18' : 'var(--t3)' }};">
              {{ $step }}
            </div>
            <span style="font-size:13px;font-weight:{{ $request['status_step'] >= $step ? '600' : '400' }};color:{{ $request['status_step'] >= $step ? 'var(--t1)' : 'var(--t3)' }};">
              {{ $label }}
            </span>
          </div>
        @endforeach
      </div>
      @endif
    </div>

  </div>
</main>

@script
<script>
    if (window.Echo) {
        window.Echo.private('admin.product-request.{{ $request['id'] ?? 0 }}')
            .listen('.message.sent', (e) => {
                if (e.sender_type === 'tenant') {
                    $wire.call('refreshRequest');
                }
            });
    }
</script>
@endscript
