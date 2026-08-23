<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Admin panel — central domain, 'admin' guard.
Broadcast::channel('admin.notifications', function ($user) {
    return true;
}, ['guards' => ['admin']]);

Broadcast::channel('admin.support.{ticketId}', function ($user, int $ticketId) {
    return $user->hasAnyPermission(['support.tickets.view', 'support.tickets.manage']);
}, ['guards' => ['admin']]);

// Tenant panel — tenant subdomain, 'tenant' guard. The tenant is already
// resolved from the subdomain by the time this runs, so just confirm the
// channel's {tenantId} matches the current tenant.
Broadcast::channel('tenant.{tenantId}.notifications', function ($user, string $tenantId) {
    return tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);

Broadcast::channel('tenant.{tenantId}.support', function ($user, string $tenantId) {
    return tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);

Broadcast::channel('tenant.{tenantId}.manufacturing.{requestId}', function ($user, string $tenantId, int $requestId) {
    return tenant('id') === $tenantId;
}, ['guards' => ['tenant']]);
