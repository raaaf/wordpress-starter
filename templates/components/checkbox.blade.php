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
    'ariaLabel' => null,
    'checked' => false,
    'indeterminate' => false,
    'disabled' => false,
    'class' => '',
])

@php
    static $checkboxCounter = 0;
    $checkboxId = $id ?? $name . '-' . (++$checkboxCounter);
    $accessibleName = $ariaLabel ?: $label;
    if (defined('WP_DEBUG') && WP_DEBUG && !$accessibleName) {
        trigger_error('x-checkbox requires a "label" or "ariaLabel" prop for accessibility.', E_USER_WARNING);
    }
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
            @if($ariaLabel && !$label) aria-label="{{ esc_attr($ariaLabel) }}" @endif
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
    document.getElementById('{{ $checkboxId }}').indeterminate = true;
</script>
@endif
