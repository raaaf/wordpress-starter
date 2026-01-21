{{--
    Radio Component - Based on Figma Design System

    @param string $name - Input name (groups radios together)
    @param string $id - Input ID
    @param string $value - Radio value
    @param string $label - Label text
    @param bool $checked - Checked state
    @param bool $disabled - Disabled state
    @param string $class - Additional CSS classes
--}}

@props([
    'name',
    'id' => null,
    'value',
    'label' => null,
    'checked' => false,
    'disabled' => false,
    'class' => '',
])

@php
    $radioId = $id ?? $name . '_' . $value;
@endphp

<label class="radio inline-flex items-center gap-2 cursor-pointer {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }} {{ $class }}">
    <span class="relative flex items-center justify-center">
        <input
            type="radio"
            name="{{ $name }}"
            id="{{ $radioId }}"
            value="{{ $value }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            class="peer sr-only"
        />

        {{-- Custom radio --}}
        <span class="w-5 h-5 rounded-full border-2 transition-all duration-200 flex items-center justify-center
            {{ $disabled
                ? 'border-line-disabled bg-surface-disabled'
                : 'border-line hover:border-line-strong peer-focus-visible:shadow-[var(--shadow-focus-ring)]'
            }}
            peer-checked:border-surface-accent
            {{ $disabled ? 'peer-checked:border-line-disabled' : '' }}
        ">
            {{-- Inner dot --}}
            <span class="w-2.5 h-2.5 rounded-full transition-all duration-200 scale-0 peer-checked:scale-100
                {{ $disabled ? 'bg-content-disabled' : 'bg-surface-accent' }}
            "></span>
        </span>
    </span>

    @if($label)
        <span class="text-base {{ $disabled ? 'text-content-disabled' : 'text-content' }}">
            {{ $label }}
        </span>
    @endif
</label>
