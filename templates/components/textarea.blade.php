{{--
    Textarea Component - Based on Figma Design System

    @param string $name - Textarea name attribute
    @param string $id - Textarea ID (defaults to name)
    @param string $value - Current value
    @param string $placeholder - Placeholder text
    @param string $label - Label text (optional)
    @param string $hint - Hint text below textarea (optional)
    @param bool $required - Required field
    @param bool $disabled - Disabled state
    @param bool $error - Error state
    @param string $errorMessage - Error message (replaces hint when set)
    @param string $size - sm, md, lg (default: md)
    @param int $rows - Number of visible rows (default based on size)
    @param string $class - Additional CSS classes

    States from Figma:
    - Default: Gray border
    - Hover: Darker border
    - Focus: Orange border with ring
    - Error: Red border, red hint text
    - Disabled: Gray background, muted text
--}}

@props([
    'name',
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
    'rows' => null,
    'class' => '',
])

@php
    $textareaId = $id ?? $name;
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // Size classes
    $sizes = [
        'sm' => [
            'textarea' => 'text-sm px-3 py-2',
            'rows' => 3,
        ],
        'md' => [
            'textarea' => 'text-base px-4 py-3',
            'rows' => 4,
        ],
        'lg' => [
            'textarea' => 'text-lg px-5 py-4',
            'rows' => 5,
        ],
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $actualRows = $rows ?? $sizeConfig['rows'];

    // Base textarea classes
    $baseClasses = 'w-full rounded-lg border bg-surface text-content placeholder:text-content-tertiary resize-y transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0';

    // State classes
    $stateClasses = match(true) {
        $disabled => 'border-line-disabled bg-surface-disabled text-content-disabled cursor-not-allowed resize-none',
        $hasError => 'border-line-error focus:border-line-error focus:ring-line-error/30',
        default => 'border-line hover:border-line-strong focus:border-line-focus focus:ring-line-focus/30',
    };
@endphp

<div class="w-full">
    {{-- Label --}}
    @if($label)
        <label for="{{ $textareaId }}" class="block text-sm font-medium text-content mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-content-error ml-0.5">*</span>
            @endif
        </label>
    @endif

    {{-- Textarea --}}
    <textarea
        name="{{ $name }}"
        id="{{ $textareaId }}"
        rows="{{ $actualRows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($hasError) aria-invalid="true" @endif
        @if($displayHint) aria-describedby="{{ $textareaId }}-hint" @endif
        class="{{ $baseClasses }} {{ $stateClasses }} {{ $sizeConfig['textarea'] }} {{ $class }}"
    >{{ $value }}</textarea>

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $textareaId }}-hint" class="mt-1.5 text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
