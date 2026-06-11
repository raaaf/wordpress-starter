{{--
    Toggle Component - Based on Figma Design System

    @param string $name - Input name
    @param string $id - Input ID (defaults to name)
    @param string $label - Label text
    @param bool $checked - Checked/On state
    @param bool $disabled - Disabled state
    @param bool $error - Error state
    @param string $errorMessage - Error message (shown below, replaces hint when set)
    @param string $hint - Hint text below control (optional)
    @param string $class - Additional CSS classes
--}}

@props([
    'name',
    'id' => null,
    'label' => null,
    'ariaLabel' => null,
    'checked' => false,
    'disabled' => false,
    'error' => false,
    'errorMessage' => null,
    'hint' => null,
    'class' => '',
])

@php
    $toggleId = $id ?? $name;
    $accessibleName = $ariaLabel ?: $label;
    if (defined('WP_DEBUG') && WP_DEBUG && !$accessibleName) {
        trigger_error('x-toggle requires a "label" or "ariaLabel" prop for accessibility.', E_USER_WARNING);
    }
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;
@endphp

<div class="inline-flex flex-col gap-1.5">
    <label class="toggle inline-flex items-center gap-3 cursor-pointer {{ $disabled ? 'cursor-not-allowed' : '' }} {{ $class }}">
        <span class="relative">
            <input
                type="checkbox"
                role="switch"
                name="{{ $name }}"
                id="{{ $toggleId }}"
                @if($checked) checked @endif
                @if($disabled) disabled @endif
                @if($ariaLabel && !$label) aria-label="{{ esc_attr($ariaLabel) }}" @endif
                @if($hasError) aria-invalid="true" @endif
                @if($displayHint) aria-describedby="{{ $toggleId }}-hint" @endif
                class="peer sr-only"
            />

            {{-- Track --}}
            <span class="block w-11 h-6 rounded-full transition-[background-color,box-shadow] duration-200
                {{ $disabled
                    ? 'bg-surface-disabled'
                    : ($hasError
                        ? 'bg-surface-tertiary peer-checked:bg-surface-accent peer-focus-visible:shadow-[var(--shadow-focus-ring)] ring-2 ring-line-error'
                        : 'bg-surface-tertiary peer-checked:bg-surface-accent peer-focus-visible:shadow-[var(--shadow-focus-ring)]'
                    )
                }}
            "></span>

            {{-- Knob --}}
            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-surface-on-color shadow-md transition-[transform,background-color] duration-200
                peer-checked:translate-x-5
                {{ $disabled ? 'bg-surface-secondary' : '' }}
            "></span>
        </span>

        @if($label)
            <span class="text-base {{ $disabled ? 'text-content-disabled' : 'text-content' }}">
                {{ $label }}
            </span>
        @endif
    </label>

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $toggleId }}-hint" class="text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
