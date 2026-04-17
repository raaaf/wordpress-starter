{{--
    Input Component - Based on Figma Design System

    @param string $name - Input name attribute
    @param string $type - Input type (text, email, password, search, tel, url, number)
    @param string $id - Input ID (defaults to name)
    @param string $value - Current value
    @param string $placeholder - Placeholder text
    @param string $label - Label text (optional)
    @param string $hint - Hint text below input (optional)
    @param bool $required - Required field
    @param bool $disabled - Disabled state
    @param bool $error - Error state
    @param string $errorMessage - Error message (replaces hint when set)
    @param string $size - sm, md, lg (default: md)
    @param string $iconLeft - Icon name for left side (optional)
    @param string $iconRight - Icon name for right side (optional)
    @param bool $clearable - Show clear button when has value
    @param string $class - Additional CSS classes for input

    States from Figma:
    - Default: Gray border with subtle shadow
    - Hover: Stronger border, enhanced shadow
    - Focus: Brand border with focus ring
    - Error: Red border, red hint text
    - Disabled: Gray background, muted text
--}}

@props([
    'name',
    'type' => 'text',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'label' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'error' => false,
    'errorMessage' => null,
    'size' => 'md',
    'iconLeft' => null,
    'iconRight' => null,
    'clearable' => false,
    'class' => '',
])

@php
    $inputId = $id ?? $name;
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // Size classes
    $sizes = [
        'sm' => [
            'input' => 'h-8 text-sm',
            'padding' => $iconLeft ? 'pl-8 pr-3' : 'px-3',
            'paddingRight' => $iconRight || $clearable ? 'pr-8' : '',
            'icon' => 'w-3.5 h-3.5',
            'iconLeft' => 'left-2.5',
            'iconRight' => 'right-2.5',
        ],
        'md' => [
            'input' => 'h-10 text-base',
            'padding' => $iconLeft ? 'pl-10 pr-4' : 'px-4',
            'paddingRight' => $iconRight || $clearable ? 'pr-10' : '',
            'icon' => 'w-4 h-4',
            'iconLeft' => 'left-3',
            'iconRight' => 'right-3',
        ],
        'lg' => [
            'input' => 'h-12 text-lg',
            'padding' => $iconLeft ? 'pl-12 pr-5' : 'px-5',
            'paddingRight' => $iconRight || $clearable ? 'pr-12' : '',
            'icon' => 'w-5 h-5',
            'iconLeft' => 'left-4',
            'iconRight' => 'right-4',
        ],
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];

    // Radius classes from Figma tokens
    $radiusClasses = [
        'sm' => 'rounded-[var(--input-sm-radius)]',
        'md' => 'rounded-[var(--input-md-radius)]',
        'lg' => 'rounded-[var(--input-lg-radius)]',
    ];
    $radiusClass = $radiusClasses[$size] ?? $radiusClasses['md'];

    // Base input classes from Figma tokens
    $baseClasses = 'input w-full border bg-surface text-content placeholder:text-content-tertiary transition-[color,background,border-color,box-shadow] duration-200 focus:outline-none';

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
        <label for="{{ $inputId }}" class="block text-sm font-medium text-content mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-content-error ml-0.5" aria-hidden="true">*</span><span class="sr-only"> ({{ __('Pflichtfeld', 'wp-starter') }})</span>
            @endif
        </label>
    @endif

    {{-- Input wrapper --}}
    <div class="relative" @if($clearable) x-data="{ hasValue: {{ $value ? 'true' : 'false' }} }" @endif>
        {{-- Left icon --}}
        @if($iconLeft)
            <div class="absolute {{ $sizeConfig['iconLeft'] }} top-1/2 -translate-y-1/2 pointer-events-none text-icon-secondary">
                <x-icon name="{{ $iconLeft }}" class="{{ $sizeConfig['icon'] }}" />
            </div>
        @endif

        {{-- Input --}}
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($hasError) aria-invalid="true" @endif
            @if($displayHint) aria-describedby="{{ $inputId }}-hint" @endif
            @if($clearable)
                x-ref="input"
                x-on:input="hasValue = $event.target.value.length > 0"
            @endif
            {{ $attributes->whereStartsWith(['x-', '@', ':', 'autocomplete', 'aria-', 'data-']) }}
            class="{{ $baseClasses }} {{ $radiusClass }} {{ $stateClasses }} {{ $sizeConfig['input'] }} {{ $sizeConfig['padding'] }} {{ $sizeConfig['paddingRight'] }} {{ $class }}"
        />

        {{-- Right icon or clear button --}}
        @if($clearable)
            <button
                type="button"
                x-show="hasValue"
                x-on:click="$refs.input.value = ''; hasValue = false; $refs.input.focus()"
                class="absolute {{ $sizeConfig['iconRight'] }} top-1/2 -translate-y-1/2 text-icon-secondary hover:text-icon transition-colors"
                @if($disabled) disabled @endif
            >
                <x-icon name="close" class="{{ $sizeConfig['icon'] }}" />
                <span class="sr-only">Eingabe löschen</span>
            </button>
        @elseif($iconRight)
            <div class="absolute {{ $sizeConfig['iconRight'] }} top-1/2 -translate-y-1/2 pointer-events-none text-icon-secondary">
                <x-icon name="{{ $iconRight }}" class="{{ $sizeConfig['icon'] }}" />
            </div>
        @endif
    </div>

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $inputId }}-hint" class="mt-1.5 text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
