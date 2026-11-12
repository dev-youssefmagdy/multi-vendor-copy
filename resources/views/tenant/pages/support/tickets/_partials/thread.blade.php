@php
    $statusOptions = \App\Models\SupportTicket::statusOptions();
@endphp

<x-tenant::chat
    id="support-ticket-chat"
    :empty="__('No messages yet.')"
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
                <x-tenant::textarea name="reply" :label="__('Reply')" rows="4" required :placeholder="__('Type your reply…')" />
                <div class="page-actions compact-actions justify-end" style="margin-top:12px;">
                    <button type="submit" class="btn btn-primary">{{ __('Send Reply') }}</button>
                </div>
            </x-tenant::form>
        </x-slot:composer>
    @else
        <x-slot:composer>
            <p class="panel-copy">{{ __('This ticket is :status and can no longer receive replies.', ['status' => strtolower($statusOptions[$ticket['status']] ?? '')]) }}</p>
        </x-slot:composer>
    @endif
</x-tenant::chat>
