{{--
    Select Component - Based on Figma Design System

    @param string $name - Select name attribute
    @param string $id - Select ID (defaults to name)
    @param array $options - Options array [value => label] or ['value' => '', 'label' => '', 'disabled' => false]
    @param string|array $selected - Currently selected value(s)
    @param string $placeholder - Placeholder option text
    @param string $label - Label text (optional)
    @param string $hint - Hint text below select (optional)
    @param bool $required - Required field
    @param bool $disabled - Disabled state
    @param bool $error - Error state
    @param string $errorMessage - Error message (replaces hint when set)
    @param string $size - sm, md, lg (default: md)
    @param string $class - Additional CSS classes

    States from Figma:
    - Default: Gray border with subtle shadow
    - Hover: Stronger border, enhanced shadow
    - Focus: Brand border with focus ring
    - Error: Red border, red hint text
    - Disabled: Gray background, muted text
--}}

@props([
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'label' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'error' => false,
    'errorMessage' => null,
    'size' => 'md',
    'class' => '',
])

@php
    $selectId = $id ?? $name;
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // Size classes
    $sizes = [
        'sm' => 'h-8 text-sm pl-3 pr-8',
        'md' => 'h-10 text-base pl-4 pr-10',
        'lg' => 'h-12 text-lg pl-5 pr-12',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Radius classes from Figma tokens
    $radiusClasses = [
        'sm' => 'rounded-[var(--input-sm-radius)]',
        'md' => 'rounded-[var(--input-md-radius)]',
        'lg' => 'rounded-[var(--input-lg-radius)]',
    ];
    $radiusClass = $radiusClasses[$size] ?? $radiusClasses['md'];

    // Base select classes from Figma tokens
    $baseClasses = 'w-full border bg-surface text-content appearance-none cursor-pointer transition-[color,background,border-color,box-shadow] duration-200 focus:outline-none';

    // State classes from Figma
    $stateClasses = match(true) {
        $disabled => 'border-line-disabled bg-surface-disabled text-content-disabled cursor-not-allowed',
        $hasError => 'border-line-error shadow-[var(--shadow-input)] focus:border-line-error focus:shadow-[var(--shadow-focus-ring)]',
        default => 'border-line shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus:shadow-[var(--shadow-focus-ring)]',
    };
@endphp

<div class="w-full">
    {{-- Label --}}
    @if($label)
        <label for="{{ $selectId }}" class="block text-sm font-medium text-content mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-content-error ml-0.5" aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span>
            @endif
        </label>
    @endif

    {{-- Select wrapper --}}
    <div class="select relative">
        <select
            name="{{ $name }}"
            id="{{ $selectId }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($hasError) aria-invalid="true" @endif
            @if($displayHint) aria-describedby="{{ $selectId }}-hint" @endif
            {{ $attributes->whereStartsWith(['x-', '@', ':', 'aria-', 'data-']) }}
            class="{{ $baseClasses }} {{ $radiusClass }} {{ $stateClasses }} {{ $sizeClass }} {{ $class }}"
        >
            @if($placeholder)
                <option value="" disabled {{ !$selected ? 'selected' : '' }}>{{ $placeholder }}</option>
            @endif

            @foreach($options as $value => $option)
                @php
                    $optionValue = is_array($option) ? ($option['value'] ?? $value) : $value;
                    $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                    $optionDisabled = is_array($option) ? ($option['disabled'] ?? false) : false;
                    $isSelected = is_array($selected) ? in_array($optionValue, $selected) : $selected == $optionValue;
                @endphp
                <option
                    value="{{ $optionValue }}"
                    {{ $isSelected ? 'selected' : '' }}
                    {{ $optionDisabled ? 'disabled' : '' }}
                >{{ $optionLabel }}</option>
            @endforeach
        </select>
    </div>

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $selectId }}-hint" class="mt-1.5 text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
