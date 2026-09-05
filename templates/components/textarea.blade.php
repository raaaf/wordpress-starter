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
    - Default: Gray border with subtle shadow
    - Hover: Stronger border, enhanced shadow
    - Focus: Brand border with focus ring
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
    $textareaId = $id ?? \WordpressStarter\Helpers\ComponentId::next((string) $name);
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // Size classes from Figma tokens
    $sizes = [
        'sm' => [
            'textarea' => 'text-base md:text-sm px-[var(--input-sm-padding-x)] py-[var(--input-sm-padding-y)] rounded-[var(--input-sm-radius)]',
            'rows' => 3,
        ],
        'md' => [
            'textarea' => 'text-base px-[var(--input-md-padding-x)] py-[var(--input-md-padding-y)] rounded-[var(--input-md-radius)]',
            'rows' => 4,
        ],
        'lg' => [
            'textarea' => 'text-lg px-[var(--input-lg-padding-x)] py-[var(--input-lg-padding-y)] rounded-[var(--input-lg-radius)]',
            'rows' => 5,
        ],
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $actualRows = $rows ?? $sizeConfig['rows'];

    // Same allowlist mechanism as x-input/x-checkbox/x-radio: plain HTML
    // attributes must be named exactly, only x-/@/:/aria-/data- may pass
    // through by prefix. Mixing 'form' into the prefix list (as before) also
    // admitted formaction/formmethod/formnovalidate/formtarget, which are not
    // textarea attributes.
    $passthroughAttrs = \WordpressStarter\Helpers\FormAttributes::passthrough(['cols', 'wrap', 'spellcheck']);
    $passthroughPrefixes = \WordpressStarter\Helpers\FormAttributes::prefixes();

    // aria-invalid and aria-describedby are rendered explicitly below (state
    // + hint id), so they are excluded from the passthrough bag to avoid a
    // duplicated attribute; a caller-supplied aria-describedby is merged in
    // instead of dropped, hint id first.
    $callerDescribedBy = trim((string) $attributes->get('aria-describedby', ''));
    $describedBy = trim(($displayHint ? $textareaId . '-hint' : '') . ' ' . $callerDescribedBy);
    $externalAttrs = $attributes->except(['aria-invalid', 'aria-describedby']);

    // Base textarea classes from Figma
    $baseClasses = 'textarea w-full border bg-surface-secondary text-content placeholder:text-content-placeholder resize-y transition-[color,background,border-color,box-shadow] duration-200';

    // State classes from Figma
    $stateClasses = match(true) {
        $disabled => 'border-line-disabled bg-surface-disabled text-content-disabled cursor-not-allowed resize-none',
        $hasError => 'border-line-error shadow-[var(--shadow-input)] focus:border-line-error focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-error)]',
        default => 'border-line-control shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]',
    };
@endphp

<div class="w-full">
    {{-- Label --}}
    @if($label)
        <label for="{{ $textareaId }}" class="block text-sm font-normal text-content mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-content-error ml-0.5" aria-hidden="true">*</span>
                <span class="sr-only"> ({{ __('Pflichtfeld', 'wp-starter') }})</span>
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
        @if($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $externalAttrs->only($passthroughAttrs) }}
        {{ $externalAttrs->whereStartsWith($passthroughPrefixes) }}
        class="{{ $baseClasses }} {{ $stateClasses }} {{ $sizeConfig['textarea'] }} {{ $class }}"
    >{{ $value }}</textarea>

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $textareaId }}-hint" class="mt-1.5 text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
