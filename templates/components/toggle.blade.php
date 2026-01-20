{{--
    Toggle Component - Based on Figma Design System

    @param string $name - Input name
    @param string $id - Input ID (defaults to name)
    @param string $label - Label text
    @param bool $checked - Checked/On state
    @param bool $disabled - Disabled state
    @param string $class - Additional CSS classes
--}}

@props([
    'name',
    'id' => null,
    'label' => null,
    'checked' => false,
    'disabled' => false,
    'class' => '',
])

@php
    $toggleId = $id ?? $name;
@endphp

<label class="inline-flex items-center gap-3 cursor-pointer {{ $disabled ? 'cursor-not-allowed' : '' }} {{ $class }}">
    <span class="relative">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $toggleId }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            class="peer sr-only"
        />

        {{-- Track --}}
        <span class="block w-11 h-6 rounded-full transition-all duration-200
            {{ $disabled
                ? 'bg-surface-disabled'
                : 'bg-surface-tertiary peer-checked:bg-surface-accent peer-focus-visible:ring-2 peer-focus-visible:ring-line-focus peer-focus-visible:ring-offset-2'
            }}
        "></span>

        {{-- Knob --}}
        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-surface-on-color shadow-md transition-all duration-200
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
