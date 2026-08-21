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

    States from Figma:
    - Default: Gradient background with shadow
    - Hover: Darker gradient, enhanced shadow
    - Active: Darkest gradient with inner shadow
    - Focus: Focus ring using accent-alpha-50
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
    // active:scale-[0.98] is a Tailwind v4 `scale` utility, not `transform` --
    // the transition list has to name the property that actually animates.
    // button--<variante> traegt keine Gestaltung, sie macht die Variante nur
    // adressierbar: fuer Flaechen, die der Utility-Klasse nicht bekannt sind
    // (invers, Markenflaeche, Hero-Scrim), und fuer Messungen.
    $baseClasses = 'button button--' . $variant . ' relative inline-flex items-center justify-center font-semibold transition-[color,background,border-color,box-shadow,scale] duration-200 no-underline cursor-pointer select-none focus-visible:outline-none active:scale-[0.98]';

    // Variants matching Figma design with gradients and shadows
    $variants = [
        'primary' => implode(' ', [
            'bg-gradient-to-b from-[var(--gradient-primary-start)] to-[var(--gradient-primary-end)]',
            // Follows the fill, not the page: see --text-on-accent in app.css.
            'text-content-on-accent',
            // Transparent statt --border-default: eine helle Haarlinie auf der
            // farbigen Fuellung liest sich als Ausfransung. Die Geometrie bleibt,
            // damit der Knopf neben den umrandeten Varianten gleich hoch steht.
            'border border-transparent',
            'shadow-[var(--shadow-button)]',
            'hover:from-[var(--gradient-primary-hover-start)] hover:to-[var(--gradient-primary-hover-end)]',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:from-[var(--gradient-primary-active-start)] active:to-[var(--gradient-primary-active-end)]',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
        'secondary' => implode(' ', [
            'bg-surface-secondary',
            'text-content',
            'border border-line',
            'shadow-[var(--shadow-button)]',
            // Nur Rand und Schatten zu wechseln war als Rueckmeldung zu leise,
            // die Flaeche geht eine Stufe mit.
            'hover:bg-surface-tertiary',
            'hover:border-line-strong',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:bg-surface-tertiary',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
        'ghost' => implode(' ', [
            'bg-transparent',
            'text-content',
            'border border-transparent',
            // Dezent unterscheidet seine Zustaende allein ueber die Flaeche.
            // Vorher kam im Aktiv-Zustand ein Rand dazu, den weder Ruhe noch
            // Hover haben; nebeneinander sahen die drei Zustaende nach drei
            // verschiedenen Knoepfen aus.
            // Die Flaechenleiter geht primary, secondary, tertiary von hell nach
            // dunkel. Vorher lag Hover auf tertiary und Aktiv auf secondary, das
            // Druecken war also heller als das Ueberfahren.
            'hover:bg-surface-secondary',
            'active:bg-surface-tertiary',
            'focus-visible:shadow-[var(--shadow-focus-ring-ghost)]',
        ]),
        'danger' => implode(' ', [
            'bg-surface-error-strong',
            // Flips with the scheme, like the content on every other fill.
            'text-content-inverse',
            'border border-transparent',
            'shadow-[var(--shadow-button)]',
            // The status ramp has only light/base/dark and --bg-error-strong
            // already takes the end of it, so there is no token to step to.
            // --bg-error-strong-hover (app.css) mixes towards black in light
            // mode and towards white in dark mode, i.e. away from whichever
            // text colour sits on top, same rule the primary gradient follows.
            'hover:bg-[var(--bg-error-strong-hover)]',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
        'inverse' => implode(' ', [
            'bg-surface',
            'text-content-brand',
            'border border-line',
            'shadow-[var(--shadow-button)]',
            'hover:bg-surface-secondary',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:bg-surface-tertiary',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
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
