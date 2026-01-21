{{--
    Checkbox Component - Based on Figma Design System

    @param string $name - Input name
    @param string $id - Input ID (defaults to name)
    @param string $value - Checkbox value
    @param string $label - Label text
    @param bool $checked - Checked state
    @param bool $indeterminate - Indeterminate state (minus icon)
    @param bool $disabled - Disabled state
    @param string $class - Additional CSS classes
--}}

@props([
    'name',
    'id' => null,
    'value' => '1',
    'label' => null,
    'checked' => false,
    'indeterminate' => false,
    'disabled' => false,
    'class' => '',
])

@php
    $checkboxId = $id ?? $name;
@endphp

<label class="checkbox inline-flex items-center gap-2 cursor-pointer {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }} {{ $class }}">
    <span class="relative flex items-center justify-center">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $checkboxId }}"
            value="{{ $value }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            @if($indeterminate) data-indeterminate="true" @endif
            class="peer sr-only"
        />

        {{-- Custom checkbox --}}
        <span class="w-5 h-5 rounded-[var(--radius-sm)] border-2 transition-all duration-200 flex items-center justify-center
            {{ $disabled
                ? 'border-line-disabled bg-surface-disabled'
                : 'border-line hover:border-line-strong peer-focus-visible:shadow-[var(--shadow-focus-ring)]'
            }}
            peer-checked:bg-surface-accent peer-checked:border-surface-accent
            {{ $disabled ? 'peer-checked:bg-surface-disabled peer-checked:border-line-disabled' : '' }}
        ">
            {{-- Check icon --}}
            <svg class="w-full h-full text-content-on-color hidden peer-checked:block" viewBox="0 0 16 16" fill="none">
                <path d="M13.5 4.5L6.5 11.5L3 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>

        {{-- Check/Minus icon (shown via CSS) --}}
        <span class="absolute inset-0 flex items-center justify-center text-content-on-color pointer-events-none opacity-0 peer-checked:opacity-100 {{ $disabled ? 'text-content-disabled' : '' }}">
            @if($indeterminate)
                <x-icon name="minus" class="w-3 h-3" />
            @else
                <x-icon name="check" class="w-3 h-3" />
            @endif
        </span>
    </span>

    @if($label)
        <span class="text-base {{ $disabled ? 'text-content-disabled' : 'text-content' }}">
            {{ $label }}
        </span>
    @endif
</label>

@if($indeterminate)
<script nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
    document.querySelector('[data-indeterminate="true"]').indeterminate = true;
</script>
@endif
