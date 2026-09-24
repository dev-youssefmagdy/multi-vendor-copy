@extends('tenant.layouts.app')
@section('title', 'Notifications')
@section('content')
    <div class="nt-head fu d0">
        <div class="nt-head-copy">
            <div class="nt-head-title">
                <h1>Notifications</h1>
                @if ($unreadCount > 0)
                    <span class="nt-unread-pill" data-notifications-unread-badge data-count="{{ $unreadCount }}">{{ $unreadCount }} unread</span>
                @endif
            </div>
            <p>View all notifications from the admin team including order updates and product assignments.</p>
        </div>

        @if ($unreadCount > 0)
            <button type="button" class="btn btn-primary nt-mark-all" data-mark-all-read="{{ route('tenant.notifications.read-all') }}">Mark all as read</button>
        @endif
    </div>

    <div class="nt-panel fu d2">
        <x-tenant::ajax-list id="notifications-list" :url="route('tenant.notifications.feed')" empty="No notifications yet">
            @include('tenant.pages.support.notifications._cols.card-list')

            <x-slot:pagination>
                <x-tenant::pagination :paginator="$notifications" mode="ajax" target="notifications-list" />
            </x-slot:pagination>
        </x-tenant::ajax-list>
    </div>
@endsection
@push('tenant-vite') @vite('resources/js/tenant/pages/support/notifications.js') @endpush
