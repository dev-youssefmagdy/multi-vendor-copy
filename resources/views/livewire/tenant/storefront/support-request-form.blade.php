{{-- Theme-neutral (inline styles on purpose): works in elora, souqify and ecommet without a CSS rebuild. --}}
@php
    $field = 'width:100%;border:1px solid #e0e0e0;border-radius:12px;padding:12px 14px;font-size:14px;background:#fff;color:#171717;outline:none;';
    $label = 'display:block;font-size:13px;font-weight:600;color:#333;margin-bottom:6px;';
    $accent = 'var(--color-primary, var(--sq-primary, #171717))';
@endphp

<section style="margin-top:40px;padding:24px;border:1px solid #ececec;border-radius:16px;background:#fafafa">
    <h2 style="font-size:20px;font-weight:700;color:#171717;margin:0 0 4px">{{ __('Send us a message') }}</h2>
    <p style="font-size:14px;color:#777;margin:0 0 20px">{{ __('Tell us what you need help with and our support team will get back to you.') }}</p>

    @if ($sent)
        <div style="padding:14px 16px;border-radius:12px;background:#e8f7ec;color:#1b6b34;font-size:14px;font-weight:600">
            {{ __('Thanks! Your message was sent. We will reply to your email soon.') }}
        </div>
    @else
        <form wire:submit="send" style="display:grid;gap:16px">
            <div>
                <label for="support-type" style="{{ $label }}">{{ __('Support type') }}</label>
                <select id="support-type" wire:model="type" style="{{ $field }}">
                    @foreach ($types as $key => $text)
                        <option value="{{ $key }}">{{ __($text) }}</option>
                    @endforeach
                </select>
                @error('type') <p style="color:#d92d20;font-size:12px;margin:4px 0 0">{{ $message }}</p> @enderror
            </div>

            <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                <div>
                    <label for="support-name" style="{{ $label }}">{{ __('Name') }}</label>
                    <input id="support-name" type="text" wire:model="name" autocomplete="name" style="{{ $field }}">
                    @error('name') <p style="color:#d92d20;font-size:12px;margin:4px 0 0">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="support-email" style="{{ $label }}">{{ __('Email') }}</label>
                    <input id="support-email" type="email" wire:model="email" autocomplete="email" style="{{ $field }}">
                    @error('email') <p style="color:#d92d20;font-size:12px;margin:4px 0 0">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="support-message" style="{{ $label }}">{{ __('Message') }}</label>
                <textarea id="support-message" rows="5" wire:model="message" style="{{ $field }}resize:vertical"></textarea>
                @error('message') <p style="color:#d92d20;font-size:12px;margin:4px 0 0">{{ $message }}</p> @enderror
            </div>

            <div>
                <button type="submit" wire:loading.attr="disabled" wire:target="send"
                        style="background:{{ $accent }};color:#fff;border:0;border-radius:999px;padding:12px 32px;font-size:15px;font-weight:600;cursor:pointer">
                    <span wire:loading.remove wire:target="send">{{ __('Send message') }}</span>
                    <span wire:loading wire:target="send">{{ __('Sending...') }}</span>
                </button>
            </div>
        </form>
    @endif
</section>
