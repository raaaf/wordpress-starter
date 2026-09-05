{{--
    Button Component - Based on Figma Design System

    @param string $url - Link URL (renders <a>)
    @param string $title - Button text
    @param string $target - Link target (_blank, _self)
    @param string $variant - primary, secondary, ghost, danger (default: primary)
    @param string $size - sm, md, lg (default: md)
    @param string $class - Additional CSS classes
    @param bool $disabled - Disabled state
    @param string $type - Button type for <button> element (submit, button, reset)
    @param array $analytics - ['event' => 'name', 'meta' => 'value'] for Rybbit

    States (rafaelalex.de design system, section 5 - pill CTAs, never boxes):
    - Default: Hairline pill, transparent fill, brand-colour border and text
    - Hover: Solid brand fill, no border, no shadow
    - Active: Deeper brand fill, 97% scale
    - Focus: 3px outline in --ring-focus, 2px offset
    - Disabled: Greyed out, no interaction
--}}

@props([
    'url' => null,
    'title' => null,
    'target' => '_self',
    'variant' => 'primary',
    'size' => 'md',
    'class' => '',
    'disabled' => false,
    'type' => 'button',
    'analytics' => null,
])

@php
    // Absent title (null) falls back to the default label instead of
    // rendering a blank button (component-tag attributes can't carry an
    // @if, so callers always pass :title). An explicit empty string is a
    // deliberate icon-only button and must stay empty, so the fallback
    // only fires on null, never on ''.
    $title = $title ?? __('Mehr erfahren', 'wp-starter');

    // Icon-only button (explicit empty title) without an accessible name:
    // same guard as x-checkbox (checkbox.blade.php:36-38).
    if (defined('WP_DEBUG') && WP_DEBUG && $title === '' && !$attributes->has('aria-label')) {
        trigger_error('x-button requires a non-empty "title" or an "aria-label" for icon-only buttons.', E_USER_WARNING);
    }

    // Normalise target: only _self/_blank are valid link targets. Anything
    // else, including a case variant like "_BLANK", falls back to _self so
    // the rel/notice logic below still fires consistently.
    $normalizedTarget = strtolower((string) $target);
    if (!in_array($normalizedTarget, ['_self', '_blank'], true)) {
        $normalizedTarget = '_self';
    }

    // Base classes - common to all buttons
    // 'button' class is used for editor CSS overrides (prevents WordPress link styling)
    // active:scale-[0.97] is a Tailwind v4 `scale` utility, not `transform` --
    // the transition list has to name the property that actually animates.
    // button--<variante> traegt keine Gestaltung, sie macht die Variante nur
    // adressierbar: fuer Flaechen, die der Utility-Klasse nicht bekannt sind
    // (invers, Markenflaeche, Hero-Scrim), und fuer Messungen.
    $baseClasses = 'button button--' . $variant . ' relative inline-flex items-center justify-center font-normal transition-[color,background,border-color,box-shadow,scale] duration-200 no-underline cursor-pointer select-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] active:scale-[0.97] motion-reduce:transform-none';

    // Variants: hairline pill at rest, flat fill on hover/active, never a
    // gradient (rafaelalex.de design system, section 5). No shadow on any
    // state, rest or hover: the Flat-at-Rest Rule applies to buttons too.
    $variants = [
        'primary' => implode(' ', [
            'text-content-brand',
            'border border-line-brand',
            'bg-[var(--bg-brand-tint)]',
            'hover:bg-surface-brand',
            'hover:text-content-on-accent',
            'hover:border-transparent',
            'active:bg-[var(--bg-brand-active)]',
            'active:text-content-on-accent',
            'active:border-transparent',
        ]),
        'secondary' => implode(' ', [
            'bg-transparent',
            'text-content',
            'border border-line',
            'hover:border-line-strong',
            'hover:bg-surface-secondary',
            'active:bg-surface-tertiary',
        ]),
        'ghost' => implode(' ', [
            'bg-transparent',
            'text-content',
            'border border-transparent',
            'hover:underline',
            'underline-offset-4',
            'active:opacity-80',
        ]),
        'danger' => implode(' ', [
            'bg-surface-error-strong',
            // Flips with the scheme, like the content on every other fill.
            'text-content-inverse',
            'border border-transparent',
            // The status ramp has only light/base/dark and --bg-error-strong
            // already takes the end of it, so there is no token to step to.
            // --bg-error-strong-hover (app.css) mixes towards black in light
            // mode and towards white in dark mode, i.e. away from whichever
            // text colour sits on top, same rule the primary fill follows.
            'hover:bg-[var(--bg-error-strong-hover)]',
        ]),
        'inverse' => implode(' ', [
            // Sits on the brand surface, so the page surface is the fill and
            // the brand colour the text. The unlayered .bg-surface rule in
            // app.css would paint the text primary again, hence the important.
            'bg-surface',
            'text-content-brand!',
            'border border-line-brand',
            'hover:bg-[var(--bg-brand-subtle)]',
            'active:bg-[var(--bg-brand-subtle)]',
        ]),
    ];

    // Disabled state overrides (same for all variants)
    $disabledClasses = 'bg-surface-disabled text-content-disabled border border-line-disabled cursor-not-allowed shadow-none hover:bg-surface-disabled hover:shadow-none active:bg-surface-disabled';

    // Sizes matching Figma with CSS variables.
    //
    // Ein Radius fuer alle drei Groessen, nicht drei. Vorher waren es 4, 8 und 12
    // Pixel; nebeneinander sah der kleine Knopf kantig und der grosse weich aus,
    // obwohl es dieselbe Komponente ist. Der Eindruck "gleiche Ecke" entsteht am
    // gleichen absoluten Radius, nicht an einem, der mit der Hoehe waechst.
    // --button-radius laesst sich je Theme setzen, die scharfe Marke bleibt scharf.
    $radius = 'rounded-[var(--button-radius,var(--button-md-radius))]';

    $sizes = [
        'sm' => 'px-[var(--button-sm-padding-x)] py-[var(--button-sm-padding-y)] text-xs min-h-[var(--button-sm-min-height)] gap-[var(--button-sm-gap)] ' . $radius,
        'md' => 'px-[var(--button-md-padding-x)] py-[var(--button-md-padding-y)] text-sm min-h-[var(--button-md-min-height)] gap-[var(--button-md-gap)] ' . $radius,
        'lg' => 'px-[var(--button-lg-padding-x)] py-[var(--button-lg-padding-y)] text-base min-h-[var(--button-lg-min-height)] gap-[var(--button-lg-gap)] ' . $radius,
    ];

    $variantClass = $disabled ? $disabledClasses : ($variants[$variant] ?? $variants['primary']);
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Analytics attributes
    $analyticsAttrs = '';
    if ($analytics && !$disabled) {
        $analyticsAttrs = 'data-rybbit-event="' . esc_attr($analytics['event'] ?? 'button_click') . '"';
        if (isset($analytics['meta'])) {
            $analyticsAttrs .= ' data-rybbit-prop-key="' . esc_attr($analytics['meta']) . '"';
        }
    }

    // A custom aria-label replaces the accessible name entirely, so the
    // "opens in new tab" sr-only span (below) never gets announced. Append
    // the notice to the label itself instead of relying on the span.
    //
    // Read the caller's own aria-label from the bag and render the composed
    // value explicitly on the element ($attributes->merge() would let the
    // caller's raw value win over this composed one, silently dropping the
    // notice), then exclude 'aria-label' from the merged bag below.
    // An empty-string aria-label ("" from a caller that composed one
    // conditionally) is not a real accessible name: treat it like an absent
    // one so the "opens in new tab" sr-only span still renders below.
    $callerAriaLabel = $attributes->get('aria-label');
    if ($callerAriaLabel === '') {
        $callerAriaLabel = null;
    }
    $linkAriaLabel = $callerAriaLabel;
    if ($linkAriaLabel !== null && $normalizedTarget === '_blank') {
        $linkAriaLabel .= ' ' . __('(öffnet in neuem Tab)', 'wp-starter');
    }
    $linkClasses = "{$baseClasses} {$variantClass} {$sizeClass} {$class}";

    // The disabled span never becomes focusable or clickable: no pointer
    // cursor, no focus ring, no active-state transition/scale. Listed
    // explicitly (layout/typography only) instead of stripping them back out
    // of $baseClasses via string surgery, which silently kept whatever
    // active/transition utilities baseClasses happened to carry.
    $disabledSpanClasses = 'button button--' . $variant . ' relative inline-flex items-center justify-center font-normal select-none';
    $disabledSpanClasses = "{$disabledSpanClasses} {$variantClass} {$sizeClass} {$class}";

    // Exact-name allowlist for everything the bag may add to a rendered
    // <a>/<button>: plain HTML attributes have to be named exactly, only
    // x-/@/:/aria-/data- may pass through by prefix. Without this, a caller
    // could add formaction, formmethod or an onXxx handler to either branch.
    $allowedAttrs = ['type', 'form', 'name', 'value', 'disabled', 'id', 'title', 'class', 'tabindex', 'autofocus'];
    $allowedPrefixes = ['x-', '@', ':', 'aria-', 'data-'];

    // The disabled span never becomes interactive: it drops the handful of
    // attributes above that only make sense on a focusable/submittable
    // element, same exclusion x-link (link.blade.php) applies to its
    // disabled span.
    $disabledSpanAttrs = array_diff($allowedAttrs, ['href', 'target', 'tabindex', 'type', 'name', 'value', 'formaction']);
@endphp

@if($url)
    @if($disabled)
        {{-- Disabled link button: rendered as a span, not an <a>, so it never
             navigates and needs no inline onclick (blocked under nonce CSP).
             Disabled means it never opens a new tab either, so the composed
             label here is the caller's own aria-label, without the "opens in
             new tab" notice that $linkAriaLabel would add. --}}
        <span aria-disabled="true"
              @if($callerAriaLabel !== null) aria-label="{{ esc_attr($callerAriaLabel) }}" @endif
              {{ $attributes->only($disabledSpanAttrs)->merge(['class' => $disabledSpanClasses]) }}
              {{ $attributes->whereStartsWith($allowedPrefixes)->except('aria-label') }}>
            {{ $title }}
            {{ $slot ?? '' }}
        </span>
    @else
        {{-- Link button --}}
        <a href="{{ esc_url($url) }}"
           target="{{ esc_attr($normalizedTarget) }}"
           @if($normalizedTarget === '_blank') rel="noopener noreferrer" @endif
           @if($linkAriaLabel !== null) aria-label="{{ esc_attr($linkAriaLabel) }}" @endif
           {!! $analyticsAttrs !!}
           {{ $attributes->only($allowedAttrs)->merge(['class' => $linkClasses]) }}
           {{ $attributes->whereStartsWith($allowedPrefixes)->except('aria-label') }}>
            {{ $title }}
            {{ $slot ?? '' }}
            @if($normalizedTarget === '_blank' && $callerAriaLabel === null)
                <span class="sr-only"> {{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span>
            @endif
        </a>
    @endif
@else
    {{-- Form button --}}
    <button type="{{ $type }}"
            @if($disabled) disabled aria-disabled="true" @endif
            {!! $analyticsAttrs !!}
            {{ $attributes->only($allowedAttrs)->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass} {$class}"]) }}
            {{ $attributes->whereStartsWith($allowedPrefixes) }}>
        {{ $title }}
        {{ $slot ?? '' }}
    </button>
@endif
