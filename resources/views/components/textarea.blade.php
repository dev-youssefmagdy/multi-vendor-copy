@props(['disabled' => false, 'error' => false, 'rows' => 4])

<div class="relative w-full group">
    <textarea rows="{{ $rows }}" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => '
                w-full bg-[var(--input)] border rounded-[8px] px-[11px] py-[9px] text-[13px] text-[var(--t1)]
                outline-none transition-all duration-200 shadow-sm placeholder:text-[var(--t3)]
                disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-gray-50/50
                ' . ($error
        ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500'
        : 'border-[var(--border)] focus:border-[var(--cyan)] focus:ring-1 focus:ring-[var(--cyan)]/50 hover:border-gray-400/80'
    )
]) !!}></textarea>
</div>