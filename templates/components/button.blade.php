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
    'title' => 'Click here',
    'target' => '_self',
    'variant' => 'primary',
    'size' => 'md',
    'class' => '',
    'disabled' => false,
    'type' => 'button',
    'analytics' => null,
])

@php
    // Base classes - common to all buttons
    // 'button' class is used for editor CSS overrides (prevents WordPress link styling)
    // active:scale-[0.97] is a Tailwind v4 `scale` utility, not `transform` --
    // the transition list has to name the property that actually animates.
    // button--<variante> traegt keine Gestaltung, sie macht die Variante nur
    // adressierbar: fuer Flaechen, die der Utility-Klasse nicht bekannt sind
    // (invers, Markenflaeche, Hero-Scrim), und fuer Messungen.
    $baseClasses = 'button button--' . $variant . ' relative inline-flex items-center justify-center font-normal transition-[color,background,border-color,box-shadow,scale] duration-200 no-underline cursor-pointer select-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] active:scale-[0.97]';

    // Variants: hairline pill at rest, flat fill on hover/active, never a
    // gradient (rafaelalex.de design system, section 5). No shadow on any
    // state, rest or hover: the Flat-at-Rest Rule applies to buttons too.
    $variants = [
        'primary' => implode(' ', [
            'text-content-brand',
            'border border-line-brand',
            'bg-transparent',
            'hover:bg-surface-brand',
            'hover:text-content-on-accent',
            'hover:border-transparent',
            'active:bg-[var(--bg-brand-active)]',
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
            'bg-surface',
            'text-content-brand',
            'border border-line',
            'hover:bg-surface-secondary',
            'active:bg-surface-tertiary',
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
@endphp

@if($url)
    {{-- Link button --}}
    <a href="{{ $disabled ? '#' : esc_url($url) }}"
       target="{{ esc_attr($target) }}"
       @if($target === '_blank' && !$disabled) rel="noopener noreferrer" @endif
       @if($disabled) aria-disabled="true" tabindex="-1" role="link" onclick="event.preventDefault(); return false;" @endif
       {!! $analyticsAttrs !!}
       {{ $attributes->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass} {$class}"]) }}>
        {{ $title }}
        {{ $slot ?? '' }}
        @if($target === '_blank' && !$attributes->has('aria-label'))
            <span class="sr-only"> {{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span>
        @endif
    </a>
@else
    {{-- Form button --}}
    <button type="{{ $type }}"
            @if($disabled) disabled aria-disabled="true" @endif
            {!! $analyticsAttrs !!}
            {{ $attributes->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass} {$class}"]) }}>
        {{ $title }}
        {{ $slot ?? '' }}
    </button>
@endif
