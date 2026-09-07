<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">{{ $request['title'] }}</h1>
        <span class="badge {{ $request['status_badge'] }}">{{ $request['status_label'] }}</span>
      </div>
      <p class="page-copy">Request #{{ $request['id'] }} &middot; Submitted {{ $request['created_at'] }}</p>
    </div>
    <a href="{{ route('tenant.product-requests.index') }}" class="btn btn-secondary">&larr; All Requests</a>
  </div>

  @if ($request['status'] !== 'rejected')
  <div class="card fu d1" style="padding:20px 24px;">
    <div style="display:flex;align-items:center;gap:0;width:100%;">
      @foreach (['pending' => 'Submitted', 'reviewing' => 'Under Review', 'in_production' => 'In Production', 'completed' => 'Completed'] as $s => $label)
        @php $step = ['pending'=>1,'reviewing'=>2,'in_production'=>3,'completed'=>4][$s]; @endphp
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;">
          <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
                      background:{{ $request['status_step'] >= $step ? 'var(--cyan)' : 'var(--border)' }};
                      color:{{ $request['status_step'] >= $step ? '#060c18' : 'var(--t3)' }};">
            {{ $step }}
          </div>
          <span style="font-size:11px;font-weight:600;color:{{ $request['status_step'] >= $step ? 'var(--t1)' : 'var(--t3)' }};text-align:center;">{{ $label }}</span>
          @if ($step < 4)
            <div style="position:absolute;top:14px;left:calc(50% + 14px);right:calc(-50% + 14px);height:2px;background:{{ $request['status_step'] > $step ? 'var(--cyan)' : 'var(--border)' }};"></div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="card fu d2" style="padding:24px;">
    <div class="stack-gap" style="gap:16px;">
      @foreach ($request['messages'] as $msg)
        <div style="display:flex;{{ $msg['is_mine'] ? 'justify-content:flex-end' : 'justify-content:flex-start' }}">
          <div style="max-width:75%;padding:14px 16px;border-radius:{{ $msg['is_mine'] ? '14px 4px 14px 14px' : '4px 14px 14px 14px' }};
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

  @if (!in_array($request['status'], ['completed', 'rejected']))
  <div class="card fu d3" style="padding:20px 24px;">
    <label class="field-label">Add a message</label>
    <textarea class="field-control" rows="4" wire:model.defer="reply"
              placeholder="Type your message&hellip;"></textarea>
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
  @endif

</main>
