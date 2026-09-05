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
    $inputId = $id !== null
        ? sanitize_html_class((string) $id, sanitize_html_class((string) $name))
        : \WordpressStarter\Helpers\ComponentId::next(sanitize_html_class((string) $name));
    $hasError = $error || $errorMessage;

    // Icon-only or label-less input without an accessible name: same guard
    // as x-checkbox/x-radio (checkbox.blade.php:43, radio.blade.php:41).
    // No dedicated $ariaLabel prop exists here, so it is read from the
    // attribute bag (still reaches the input via the aria- prefix
    // passthrough below).
    if (defined('WP_DEBUG') && WP_DEBUG && !$label && !$attributes->has('aria-label')) {
        trigger_error('x-input requires a "label" prop or an "aria-label" attribute for accessibility.', E_USER_WARNING);
    }

    // Shared allowlist for x-input/x-checkbox/x-radio: plain HTML/validation
    // attributes must be named exactly, only x-/@/:/aria-/data- may pass through
    // by prefix. A "form" prefix admitted formaction/formmethod too, which are
    // not validation attributes.
    $passthroughAttrs = \WordpressStarter\Helpers\FormAttributes::passthrough();
    $passthroughPrefixes = \WordpressStarter\Helpers\FormAttributes::prefixes();
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // aria-describedby is also rendered explicitly below (pointing at the
    // hint/error <p>), so a caller-supplied aria-describedby (which would
    // otherwise reach the input a second time via the aria- prefix
    // passthrough) is merged into one value instead, hint id first.
    $callerDescribedBy = $attributes->get('aria-describedby');
    $describedByIds = array_filter([$displayHint ? $inputId . '-hint' : null, $callerDescribedBy]);
    $describedBy = $describedByIds ? implode(' ', $describedByIds) : null;

    // Size classes
    // 'padding' puts the icon's right edge plus an 8px gap between the two
    // ('iconLeft' offset + 'icon' width + 8px), the same icon-to-text gap
    // used elsewhere in the theme (e.g. --button-md-gap).
    $sizes = [
        // Below 16px iOS Safari zooms the page on focus, so the small size
        // stays at 16px on phones and drops to 14px from md up.
        'sm' => [
            'input' => 'h-8 text-base md:text-sm',
            'padding' => $iconLeft ? 'pl-8 pr-[var(--input-sm-padding-x)]' : 'px-[var(--input-sm-padding-x)]',
            'paddingRight' => $iconRight || $clearable ? 'pr-8' : '',
            'icon' => 'w-3.5 h-3.5',
            'iconLeft' => 'left-2.5',
            'iconRight' => 'right-2.5',
        ],
        'md' => [
            'input' => 'h-10 text-base',
            'padding' => $iconLeft ? 'pl-9 pr-[var(--input-md-padding-x)]' : 'px-[var(--input-md-padding-x)]',
            'paddingRight' => $iconRight || $clearable ? 'pr-10' : '',
            'icon' => 'w-4 h-4',
            'iconLeft' => 'left-3',
            'iconRight' => 'right-3',
        ],
        'lg' => [
            'input' => 'h-12 text-lg',
            'padding' => $iconLeft ? 'pl-11 pr-[var(--input-lg-padding-x)]' : 'px-[var(--input-lg-padding-x)]',
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
    $baseClasses = 'input w-full border bg-surface-secondary text-content placeholder:text-content-placeholder transition-[color,background,border-color,box-shadow] duration-200';

    // State classes from Figma
    $stateClasses = match(true) {
        $disabled => 'border-line-disabled bg-surface-disabled text-content-disabled cursor-not-allowed',
        $hasError => 'border-line-error shadow-[var(--shadow-input)] focus:border-line-error focus:outline-3 focus:outline-offset-2 focus:outline-[var(--color-error)]',
        default => 'border-line-control shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus:outline-3 focus:outline-offset-2 focus:outline-[var(--ring-focus)]',
    };
@endphp

<div class="w-full">
    {{-- Label --}}
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-normal text-content mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-content-error ml-0.5" aria-hidden="true">*</span><span class="sr-only"> ({{ __('Pflichtfeld', 'wp-starter') }})</span>
            @endif
        </label>
    @endif

    {{-- Input wrapper --}}
    <div class="relative" @if($clearable) x-data="{ hasValue: {{ $value ? 'true' : 'false' }} }" x-init="hasValue = $refs.input.value.length > 0" @endif>
        {{-- Left icon --}}
        {{-- inset-y-0 + flex items-center, not top-1/2 -translate-y-1/2: the icon
             is an inline-block SVG (align-middle, see icon.blade.php), so its
             line-box baseline math left it 1px off centre under a transform.
             Flex alignment centres on the icon's own box instead. --}}
        @if($iconLeft)
            <div class="absolute {{ $sizeConfig['iconLeft'] }} inset-y-0 flex items-center pointer-events-none text-icon-secondary">
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
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($clearable)
                x-ref="input"
                x-on:input="hasValue = $event.target.value.length > 0"
                x-on:change="hasValue = $event.target.value.length > 0"
            @endif
            {{ $attributes->only($passthroughAttrs) }}
            {{ $attributes->whereStartsWith($passthroughPrefixes)->except('aria-describedby') }}
            class="{{ $baseClasses }} {{ $radiusClass }} {{ $stateClasses }} {{ $sizeConfig['input'] }} {{ $sizeConfig['padding'] }} {{ $sizeConfig['paddingRight'] }} {{ $class }}"
        />

        {{-- Right icon or clear button --}}
        @if($clearable)
            {{-- min-h-11!/min-w-11!: 44px Hit-Flaeche wie bei den Icon-Buttons in
                 team.blade.php, das Icon selbst bleibt in seiner urspruenglichen
                 Groesse (sm/md/lg), nur die Klickflaeche waechst per Ueberhang. --}}
            <button
                type="button"
                x-show="hasValue"
                x-on:click="$refs.input.value = ''; hasValue = false; $refs.input.focus()"
                class="absolute {{ $sizeConfig['iconRight'] }} top-1/2 -translate-y-1/2 inline-flex items-center justify-center min-h-11! min-w-11! text-icon-secondary hover:text-icon transition-colors focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                @if($disabled) disabled @endif
            >
                <x-icon name="close" class="{{ $sizeConfig['icon'] }}" />
                <span class="sr-only">{{ __('Eingabe löschen', 'wp-starter') }}</span>
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
