{{--
    Radio Component - Based on Figma Design System

    @param string $name - Input name (groups radios together)
    @param string $id - Input ID
    @param string $value - Radio value
    @param string $label - Label text
    @param bool $checked - Checked state
    @param bool $disabled - Disabled state
    @param bool $error - Error state
    @param string $errorMessage - Error message (shown below, replaces hint when set)
    @param string $hint - Hint text below control (optional)
    @param string $class - Additional CSS classes
--}}

@props([
    'name',
    'id' => null,
    'value',
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
    $radioId = $id !== null
        ? sanitize_html_class((string) $id, sanitize_html_class($name . '_' . $value))
        : \WordpressStarter\Helpers\ComponentId::next(sanitize_html_class((string) $name . '_' . (string) $value));
    $accessibleName = $ariaLabel ?: $label;

    // Same allowlist as x-input (input.blade.php), plus 'required': unlike
    // x-input, x-radio has no dedicated $required prop, so it must stay
    // reachable as a plain passthrough attribute here.
    $passthroughAttrs = \WordpressStarter\Helpers\FormAttributes::passthrough(['required']);
    $passthroughPrefixes = \WordpressStarter\Helpers\FormAttributes::prefixes();
    if (defined('WP_DEBUG') && WP_DEBUG && !$accessibleName) {
        trigger_error('x-radio requires a "label" or "ariaLabel" prop for accessibility.', E_USER_WARNING);
    }
    $hasError = $error || $errorMessage;
    $displayHint = $hasError && $errorMessage ? $errorMessage : $hint;

    // aria-describedby is also rendered explicitly below (pointing at the
    // hint/error <p>), so a caller-supplied aria-describedby (which would
    // otherwise reach the input a second time via the aria- prefix
    // passthrough) is merged into one value instead, hint id first.
    $callerDescribedBy = $attributes->get('aria-describedby');
    $describedByIds = array_filter([$displayHint ? $radioId . '-hint' : null, $callerDescribedBy]);
    $describedBy = $describedByIds ? implode(' ', $describedByIds) : null;
@endphp

<div class="inline-flex flex-col gap-1.5">
    <label class="radio inline-flex items-center gap-3 cursor-pointer {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }} {{ $class }}">
        <span class="relative flex items-center justify-center">
            <input
                type="radio"
                name="{{ $name }}"
                id="{{ $radioId }}"
                value="{{ $value }}"
                @if($checked) checked @endif
                @if($disabled) disabled @endif
                @if($ariaLabel && !$label) aria-label="{{ esc_attr($ariaLabel) }}" @endif
                @if($hasError) aria-invalid="true" @endif
                @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->only($passthroughAttrs)->merge(['class' => 'peer sr-only']) }}
                {{ $attributes->whereStartsWith($passthroughPrefixes)->except('aria-describedby') }}
            />

            {{-- Custom radio --}}
            <span class="w-5 h-5 rounded-full border-2 transition-[background-color,border-color,box-shadow] duration-200 flex items-center justify-center
                {{ $disabled
                    ? 'border-line-disabled bg-surface-disabled'
                    : ($hasError
                        ? 'border-line-error peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[var(--color-error)]'
                        : 'border-line-control hover:border-line-strong peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[var(--ring-focus)]'
                    )
                }}
                peer-checked:border-surface-accent
                peer-checked:*:scale-100
                {{ $disabled ? 'peer-checked:border-line-disabled' : '' }}
            ">
                {{-- Inner dot. Die Skalierung wird vom Ring aus gesteuert (peer-checked:*:),
                     weil der Punkt ein Kind des Rings ist und nicht sein Geschwister — ein
                     peer-checked: direkt am Punkt erzeugt `.peer:checked ~ .punkt` und das
                     kann strukturell nie matchen. Der markierte Radio blieb dadurch hohl. --}}
                <span class="w-2.5 h-2.5 rounded-full transition-[scale,background-color] duration-200 scale-0
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

    {{-- Hint / Error message --}}
    @if($displayHint)
        <p id="{{ $radioId }}-hint" class="text-sm {{ $hasError ? 'text-content-error' : 'text-content-secondary' }}">
            {{ $displayHint }}
        </p>
    @endif
</div>
