@php
    /** @var array{state: string, error: ?string, checked_at: ?\Carbon\CarbonImmutable, config: ?array} $mailStatus */
    $checkedAt = $mailStatus['checked_at']?->diffForHumans();
    $from = $mailStatus['config']['from_address'] ?? null;
@endphp

<div data-mail-status>
    @switch($mailStatus['state'])
        @case(\App\Services\Mail\TenantMailHealth::VERIFIED)
            <x-tenant::alert type="success" :title="__('Sending through your mail server')">
                {{ __('Store emails are sent from :email.', ['email' => $from]) }}
                @if($checkedAt) {{ __('Last verified :time.', ['time' => $checkedAt]) }} @endif
            </x-tenant::alert>
            @break

        @case(\App\Services\Mail\TenantMailHealth::FAILED)
            <x-tenant::alert type="danger" :title="__('Your mail server is not working')">
                {{ __('Error: :error', ['error' => $mailStatus['error']]) }}
                @if($checkedAt) ({{ $checkedAt }}) @endif
                <br>{{ __('Your store emails are being sent through the platform mail server. Fix the settings below and save, or use "Send Test Email" to check again.') }}
            </x-tenant::alert>
            @break

        @case(\App\Services\Mail\TenantMailHealth::UNVERIFIED)
            <x-tenant::alert type="warning" :title="__('Mail server not verified yet')">
                {{ __('Store emails are sent from :email; if your server fails they fall back to the platform mail server. Save or use "Send Test Email" to verify it now.', ['email' => $from]) }}
            </x-tenant::alert>
            @break

        @default
            <x-tenant::alert type="info" :title="__('Using the platform mail server')">
                {{ __('Your store emails are sent through the platform mail server. Fill in the settings below to send from your own email address.') }}
            </x-tenant::alert>
    @endswitch
</div>
