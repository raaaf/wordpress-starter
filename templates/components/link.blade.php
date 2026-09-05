{{--
    Link Component - Based on Figma Design System

    @param string $url - Link URL
    @param string $target - Link target (_blank, _self)
    @param string $variant - accent, dark (default: accent)
    @param string $size - sm, md, lg (default: md)
    @param string $iconLeft - Icon name for left side
    @param string $iconRight - Icon name for right side
    @param bool $disabled - Disabled state
    @param string $class - Additional CSS classes

    States from Figma:
    - Default: Underlined link with accent/dark color
    - Hover: Color shifts to hover variant
    - Visited: Tertiary text color
    - Disabled: Muted color, no interaction
--}}

@props([
    'url' => '#',
    'target' => '_self',
    'variant' => 'accent',
    'size' => 'md',
    'iconLeft' => null,
    'iconRight' => null,
    'disabled' => false,
    'class' => '',
    'ariaLabel' => null,
])

@php
    $sizes = [
        'sm' => 'text-sm gap-1',
        'md' => 'text-base gap-1.5',
        'lg' => 'text-lg gap-2',
    ];

    $iconSizes = [
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
    ];

    $variants = [
        'accent' => 'text-content-link hover:text-content-link-hover',
        'dark' => 'text-content hover:text-content-secondary',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
    $variantClass = $disabled
        ? 'text-content-disabled cursor-not-allowed'
        : 'cursor-pointer ' . ($variants[$variant] ?? $variants['accent']);

    // Normalise target: only _self/_blank are valid link targets. Anything
    // else, including a case variant like "_BLANK", falls back to _self so
    // the rel/notice logic below still fires consistently, same as x-button
    // (button.blade.php).
    $normalizedTarget = strtolower((string) $target);
    if (!in_array($normalizedTarget, ['_self', '_blank'], true)) {
        $normalizedTarget = '_self';
    }

    // A custom aria-label replaces the accessible name entirely, so the
    // "opens in new tab" sr-only span (below) never gets announced. Append
    // the notice to the label itself instead of relying on the span, same
    // composition as x-button (button.blade.php).
    $linkAriaLabel = $ariaLabel;
    if ($linkAriaLabel && $normalizedTarget === '_blank' && !$disabled) {
        $linkAriaLabel .= ' ' . __('(öffnet in neuem Tab)', 'wp-starter');
    }

    $linkClasses = "link inline-flex items-center font-normal underline underline-offset-4 transition-colors duration-200 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] {$variantClass} {$sizeClass} {$class}";
@endphp

{{-- Disabled link: rendered as a span, not an <a>, so it never navigates. --}}
<{{ $disabled ? 'span' : 'a' }}
   @if(!$disabled) href="{{ esc_url($url) }}" @endif
   @if(!$disabled) target="{{ esc_attr($normalizedTarget) }}" @endif
   @if($normalizedTarget === '_blank' && !$disabled) rel="noopener noreferrer" @endif
   @if($disabled) aria-disabled="true" @endif
   @if($linkAriaLabel) aria-label="{{ esc_attr($linkAriaLabel) }}" @endif
   {{-- Merged (not printed as a plain trailing class="..." attribute): a
        caller-supplied class in the bag would otherwise render as an earlier
        duplicate "class" attribute, and browsers keep only the first one,
        silently dropping every component style below. --}}
   {{ ($disabled ? $attributes->except(['tabindex', 'href', 'target']) : $attributes)->merge(['class' => $linkClasses]) }}>
    @if($iconLeft)
        <x-icon name="{{ $iconLeft }}" class="{{ $iconSize }}" />
    @endif

    {{--
        Achtung bei der Verwendung: der Zeilenumbruch vor </a> landet als
        Leerzeichen im Link. Steht direkt hinter <x-link> ein Satzzeichen,
        rendert der Browser "(Google) ." mit Luecke davor.

        Hier ist das nicht zu beheben: Blade bricht sowohl beim einzeiligen
        @if mit Komponenten-Tag als auch beim Kommentar direkt nach @endif
        (beides ausprobiert, beides ergibt "unexpected token endif").
        Deshalb an der Verwendungsstelle loesen: Satzzeichen in den Linktext
        ziehen oder den Satz so bauen, dass keines folgt.
    --}}
    {{ $slot }}

    @if($iconRight)
        <x-icon name="{{ $iconRight }}" class="{{ $iconSize }}" />
    @endif

    @if($normalizedTarget === '_blank' && !$ariaLabel)
        <span class="sr-only"> {{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span>
    @endif
</{{ $disabled ? 'span' : 'a' }}>
