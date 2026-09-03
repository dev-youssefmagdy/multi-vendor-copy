@props(['disabled' => false, 'icon' => null, 'error' => false])

<div class="relative w-full group">
    @if($icon)
        <div class="absolute inset-y-0 left-0 flex items-center pl-[11px] pointer-events-none text-[var(--t3)] group-focus-within:text-[var(--cyan)] transition-colors duration-200" aria-hidden="true">
            {!! $icon !!}
        </div>
    @endif

    <input
        style="padding: 7px 7px!important;"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => '
                w-full bg-[var(--input)] border rounded-[8px] text-[13px] text-[var(--t1)]
                outline-none transition-all duration-200 shadow-sm placeholder:text-[var(--t3)]
                disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-gray-50/50
                '
                . ($error
                    ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500'
                    : 'border-[var(--border)] focus:border-[var(--cyan)] focus:ring-1 focus:ring-[var(--cyan)]/50 hover:border-gray-400/80'
                )
                . ' '
                . ($icon ? 'pl-[36px] pr-[11px]' : 'px-[11px]')
        ]) !!}
    >
</div>
