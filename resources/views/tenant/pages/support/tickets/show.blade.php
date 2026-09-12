@extends('tenant.layouts.app')

@section('title', $ticket['subject'])

@section('content')
    <x-tenant::page-header :title="$ticket['subject']" badge="Help Desk" description="Conversation history with the marketplace admin team.">
        <x-slot:meta>
            <span class="badge {{ in_array($ticket['status'], ['resolved', 'closed']) ? 'badge-green' : 'badge-cyan' }}">
                {{ $statusOptions[$ticket['status']] ?? '' }}
            </span>
        </x-slot:meta>
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.support.index') }}">&larr; Tickets</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="card fu d1">
        <div class="panel-head mb-5">
            <div>
                <h3 class="panel-title">Conversation</h3>
                <p class="panel-copy">
                    {{ $categoryOptions[$ticket['category']] ?? '' }} &middot;
                    {{ $priorityOptions[$ticket['priority']] ?? '' }} priority
                </p>
            </div>
        </div>

        <div id="support-thread-container" data-thread-container data-thread-url="{{ route('tenant.support.thread', $ticket['id']) }}">
            @include('tenant.pages.support.tickets._partials.thread', ['ticket' => $ticket])
        </div>
    </div>

    <script type="application/json" id="support-ticket-data">{"ticketId": {{ $ticket['id'] }}}</script>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/support/ticket-show.js')
@endpush
