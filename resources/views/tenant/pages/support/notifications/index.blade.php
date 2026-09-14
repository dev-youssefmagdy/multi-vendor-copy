@extends('tenant.layouts.app')
@section('title', 'Notifications')
@section('content')
    <x-tenant::page-header title="Notifications" badge="Inbox" description="View all notifications from the admin team including order updates and product assignments.">
        <x-slot:meta>
            @if ($unreadCount > 0)
                <span class="badge badge-amber" data-notifications-unread-badge>{{ $unreadCount }} unread</span>
            @endif
        </x-slot:meta>
        @if ($unreadCount > 0)
            <x-slot:actions>
                <x-tenant::btn type="button" variant="secondary" data-mark-all-read="{{ route('tenant.notifications.read-all') }}">Mark All as Read</x-tenant::btn>
            </x-slot:actions>
        @endif
    </x-tenant::page-header>

    <div class="card fu d2 table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">All Notifications</h3>
                <p class="panel-copy">{{ $notifications->total() }} total notifications.</p>
            </div>
        </div>

        <x-tenant::ajax-list id="notifications-list" :url="route('tenant.notifications.feed')" empty="No notifications yet">
            @include('tenant.pages.support.notifications._cols.card-list')

            <x-slot:pagination>
                <x-tenant::pagination :paginator="$notifications" mode="ajax" target="notifications-list" />
            </x-slot:pagination>
        </x-tenant::ajax-list>
    </div>
@endsection
@push('tenant-vite') @vite('resources/js/tenant/pages/support/notifications.js') @endpush
