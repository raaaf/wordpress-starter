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
    @param string $size - sm, md, lg (default: md), the visual scale, unrelated to $visibleRows
    @param int $visibleRows - number of visible rows when $multiple is set (rendered as the native "size" attribute).
           Detection covers a literal "multiple" attribute and an Alpine
           ":multiple"/"x-bind:multiple" binding by presence only: a dynamic
           binding's runtime value is unknown at render time, so its mere
           presence is treated as multiple for the "size" attribute.
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
    'visibleRows' => null,
    'class' => '',
])

@php
    $selectId = $id ?? \WordpressStarter\Helpers\ComponentId::next((string) $name);
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // Reference allowlist for x-select: plain HTML attributes must be named
    // exactly, only x-/@/:/aria-/data- may pass through by prefix. A "form"
    // prefix admitted formaction/formmethod too (same trap as input.blade.php).
    $passthroughAttrs = ['autocomplete', 'form', 'multiple'];
    $passthroughPrefixes = \WordpressStarter\Helpers\FormAttributes::prefixes();
    // Alpine binds the native "multiple" attribute via :multiple/x-bind:multiple
    // rather than passing it as a plain HTML attribute; has('multiple') alone
    // misses that case, so the visibleRows "size" logic below never engaged
    // for an Alpine-driven multi-select.
    $isMultiple = $attributes->has('multiple')
        || $attributes->has(':multiple')
        || $attributes->has('x-bind:multiple');

    // aria-invalid and aria-describedby are rendered explicitly below, so
    // they are excluded from the passthrough bag to avoid a duplicated
    // attribute; a caller-supplied aria-describedby is merged in instead of
    // dropped, hint id first.
    $callerDescribedBy = trim((string) $attributes->get('aria-describedby', ''));
    $describedBy = trim(($displayHint ? $selectId . '-hint' : '') . ' ' . $callerDescribedBy);
    $externalAttrs = $attributes->except(['aria-invalid', 'aria-describedby']);

    // Size classes
    $sizes = [
        'sm' => 'h-8 text-base md:text-sm pl-[var(--input-sm-padding-x)] pr-8',
        'md' => 'h-10 text-base pl-[var(--input-md-padding-x)] pr-10',
        'lg' => 'h-12 text-lg pl-[var(--input-lg-padding-x)] pr-12',
    ];

    // Chevron icon positioning per size
    $chevronSizes = [
        'sm' => ['wrap' => 'right-2.5', 'icon' => 'w-3.5 h-3.5'],
        'md' => ['wrap' => 'right-3', 'icon' => 'w-4 h-4'],
        'lg' => ['wrap' => 'right-4', 'icon' => 'w-5 h-5'],
    ];
    $chevronConfig = $chevronSizes[$size] ?? $chevronSizes['md'];

    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Radius classes from Figma tokens
    $radiusClasses = [
        'sm' => 'rounded-[var(--input-sm-radius)]',
        'md' => 'rounded-[var(--input-md-radius)]',
        'lg' => 'rounded-[var(--input-lg-radius)]',
    ];
    $radiusClass = $radiusClasses[$size] ?? $radiusClasses['md'];

    // Base select classes from Figma tokens
    $baseClasses = 'w-full border bg-surface-secondary text-content appearance-none cursor-pointer transition-[color,background,border-color,box-shadow] duration-200';

    // State classes from Figma
    $stateClasses = match(true) {
        $disabled => 'border-line-disabled bg-surface-disabled text-content-disabled cursor-not-allowed',
        $hasError => 'border-line-error shadow-[var(--shadow-input)] focus:border-line-error focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-error)]',
        default => 'border-line-control shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]',
    };
@endphp

<div class="w-full">
    {{-- Label --}}
    @if($label)
        <label for="{{ $selectId }}" class="block text-sm font-normal text-content mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-content-error ml-0.5" aria-hidden="true">*</span><span class="sr-only"> ({{ __('Pflichtfeld', 'wp-starter') }})</span>
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
            @if($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if($isMultiple && $visibleRows) size="{{ (int) $visibleRows }}" @endif
            {{ $externalAttrs->only($passthroughAttrs) }}
            {{ $externalAttrs->whereStartsWith($passthroughPrefixes) }}
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

        {{-- Chevron icon --}}
        <div class="absolute {{ $chevronConfig['wrap'] }} top-1/2 -translate-y-1/2 pointer-events-none {{ $disabled ? 'text-icon-disabled' : 'text-icon-secondary' }}">
            <x-icon name="chevron-down" class="{{ $chevronConfig['icon'] }}" />
        </div>
    </div>

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $selectId }}-hint" class="mt-1.5 text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
