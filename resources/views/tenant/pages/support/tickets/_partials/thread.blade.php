@php
    $statusOptions = \App\Models\SupportTicket::statusOptions();
@endphp

<x-tenant::chat
    id="support-ticket-chat"
    empty="No messages yet."
    :messages="$ticket['messages']"
>
    @if(!($ticket['is_closed'] ?? false))
        <x-slot:composer>
            <x-tenant::form
                id="support-reply-form"
                action="{{ route('tenant.support.replies', $ticket['id']) }}"
                validate="{{ route('tenant.support.replies.validate', $ticket['id']) }}"
                success="none"
            >
                <x-tenant::textarea name="reply" label="Reply" rows="4" required placeholder="Type your reply&hellip;" />
                <div class="page-actions compact-actions justify-end" style="margin-top:12px;">
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </x-tenant::form>
        </x-slot:composer>
    @else
        <x-slot:composer>
            <p class="panel-copy">This ticket is {{ strtolower($statusOptions[$ticket['status']] ?? '') }} and can no longer receive replies.</p>
        </x-slot:composer>
    @endif
</x-tenant::chat>
