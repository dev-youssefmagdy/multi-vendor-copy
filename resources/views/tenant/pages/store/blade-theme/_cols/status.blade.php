<x-tenant::badge :color="match($theme->status) {
    'approved' => 'green',
    'rejected' => 'red',
    default => 'amber',
}">{{ ucfirst($theme->status) }}</x-tenant::badge>
@if($theme->status === 'rejected' && $theme->rejection_reason)
    <div class="field-hint">{{ e($theme->rejection_reason) }}</div>
@endif
@if($theme->is_active)
    <x-tenant::badge color="cyan">Active</x-tenant::badge>
@endif
