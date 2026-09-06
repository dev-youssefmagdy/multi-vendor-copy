<main id="mn" wire:key="ticket-{{ $ticket['id'] ?? 0 }}">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
                <span class="badge {{ in_array($ticket['status'] ?? '', ['resolved', 'closed']) ? 'badge-green' : 'badge-cyan' }}">
                    {{ $statusOptions[$ticket['status'] ?? ''] ?? '' }}
                </span>
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('admin.support.index') }}">← Tickets</a>
        </div>
    </div>

    <div class="content-grid section-gap">
        <div class="card fu d1">
            <div class="panel-head mb-5">
                <div>
                    <h3 class="panel-title">Conversation</h3>
                    <p class="panel-copy">
                        {{ $categoryOptions[$ticket['category'] ?? ''] ?? '' }} ·
                        {{ $priorityOptions[$ticket['priority'] ?? ''] ?? '' }} priority
                    </p>
                </div>
            </div>

            <div class="content-note-stack" id="support-thread">
                @forelse ($ticket['messages'] ?? [] as $message)
                    <div class="content-note">
                        <strong>{{ $message['sender_name'] }} ({{ $message['sender_type'] === 'admin' ? 'Admin' : 'Tenant' }})</strong>
                        <span style="opacity:.6;font-size:12px;margin-left:6px">{{ $message['created_at'] }}</span>
                        <p class="panel-copy panel-copy-spaced">{{ $message['body'] }}</p>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-title">No messages yet</div>
                    </div>
                @endforelse
            </div>

            @if ($canManage)
                <form wire:submit="sendReply" class="page-stack" style="margin-top:16px">
                    <x-textarea rows="4" wire:model.defer="reply" :error="$errors->has('reply')" placeholder="Type your reply…" />
                    @error('reply')<div class="field-error">{{ $message }}</div>@enderror
                    <div class="page-actions compact-actions justify-end">
                        <x-btn type="submit" wire:loading.attr="disabled" wire:target="sendReply">
                            <span wire:loading.remove wire:target="sendReply">Send Reply</span>
                            <span wire:loading wire:target="sendReply">Sending…</span>
                        </x-btn>
                    </div>
                </form>
            @endif
        </div>

        <div class="card fu d2">
            <h3 class="panel-title">Ticket Info</h3>
            <div class="content-note-stack">
                <div class="content-note"><strong>Tenant.</strong> {{ $ticket['tenant_id'] ?? '' }}</div>
                <div class="content-note"><strong>Category.</strong> {{ $categoryOptions[$ticket['category'] ?? ''] ?? '' }}</div>
                <div class="content-note"><strong>Priority.</strong> {{ $priorityOptions[$ticket['priority'] ?? ''] ?? '' }}</div>
            </div>

            @if ($canManage)
                <form wire:submit="updateStatus" class="page-stack" style="margin-top:16px">
                    <label class="field-label">Status</label>
                    <x-select wire:model.defer="statusSelection">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <div class="page-actions compact-actions justify-end">
                        <x-btn type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="updateStatus">
                            Update Status
                        </x-btn>
                    </div>
                </form>
            @endif
        </div>
    </div>
</main>

@script
<script>
    if (window.Echo) {
        window.Echo.private('admin.support.{{ $ticket['id'] ?? 0 }}')
            .listen('.message.sent', (e) => {
                if (e.sender_type === 'tenant') {
                    $wire.call('refreshTicket');
                }
            });
    }
</script>
@endscript
