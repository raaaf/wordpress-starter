{{--
    Section Component - Wrapper for content sections

    @param string $background - bg color: primary, secondary, tertiary, brand, brand-subtle, inverse
    @param string $padding - sm, md, lg, xl (default: lg)
    @param string|null $spacing - editor override for $padding: default, sm, xl, none
    @param string|null $width - editor override: container (default) or full
    @param string $anchor - HTML ID for anchor links (slugged via
        sanitize_title(); switching to it from the previous transliteration
        changed the rendered id of anchors with accented characters, so
        older external links to those anchors on a live site stop resolving)
    @param string $class - Additional CSS classes
    @param bool $container - Wrap content in container (default: true)
    @param bool|null $animate - Enable scroll animation (null = use global setting)
--}}

@props([
    'background' => 'primary',
    'padding' => 'lg',
    'spacing' => null,
    'width' => null,
    'anchor' => null,
    'class' => '',
    'container' => true,
    'animate' => null,
])

@php
    $backgrounds = [
        'primary' => 'bg-surface',
        'secondary' => 'bg-surface-secondary',
        'tertiary' => 'bg-surface-tertiary',
        'brand' => 'bg-surface-brand text-content-on-brand',
        'brand-subtle' => 'bg-surface-brand-subtle',
        'inverse' => 'bg-surface-inverse text-content-inverse',
    ];

    $paddings = [
        'none' => '',
        'sm' => 'py-8 md:py-12',
        'md' => 'py-12 md:py-16',
        'lg' => 'py-16 md:py-20',
        'xl' => 'py-20 md:py-28',
    ];

    $bgClass = $backgrounds[$background] ?? $backgrounds['primary'];

    // The editor field wins over the template default, but only when it says
    // something: "default" means "keep the rhythm the template designed".
    $paddingKey = ($spacing && $spacing !== 'default' && isset($paddings[$spacing])) ? $spacing : $padding;
    $paddingClass = $paddings[$paddingKey] ?? $paddings['lg'];

    // Volle Breite laesst nur die Maximalbreite fallen, nicht den seitlichen
    // Rand: ohne ihn klebt der Inhalt auf dem Handy am Fensterrand.
    $containerClass = $width === 'full'
        ? 'w-full px-4 sm:px-6 lg:px-8'
        : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';

    // Determine if animations should be enabled
    $globalAnimations = \WordpressStarter\Acf\Fields::option('animations_enabled', false);
    $shouldAnimate = $animate ?? $globalAnimations;

    // Two flexible-content sections can carry the same anchor (same heading
    // text, copy-pasted layout, ...). Duplicate ids break in-page links, so
    // the first occurrence on the request keeps its id unchanged and every
    // repeat gets "-2", "-3", ... appended.
    $anchorId = null;
    if ($anchor) {
        // sanitize_html_class() strips accented characters outright instead of
        // transliterating them, so an anchor like "Über uns" collapsed to "ber-uns"
        // (or worse, an empty id). ComponentId::anchor() is WordPress' own slug
        // generator (sanitize_title()): it transliterates umlauts via
        // remove_accents() first. Every template that builds an "#anchor" link
        // from the same raw field (e.g. styleguide-nav.blade.php) must slugify
        // through the same helper, or the link target stops matching this id.
        $anchorId = \WordpressStarter\Helpers\ComponentId::anchor($anchor);
        if ($anchorId === '') {
            $anchorId = \WordpressStarter\Helpers\ComponentId::next('section');
        }
        $anchorId = \WordpressStarter\Helpers\ComponentId::unique($anchorId);
    }
@endphp

<section
    @if($anchorId) id="{{ $anchorId }}" @endif
    @if($shouldAnimate)
        x-data="{ shown: false }"
        x-init="if (location.hash) shown = true"
        x-on:hashchange.window="shown = true"
        x-intersect.once="shown = true"
        :class="{ 'is-visible': shown }"
    @endif
    {{ $attributes->except('id')->merge(['class' => "section {$bgClass} {$paddingClass} {$class}"]) }}
>
    @if($container)
        <div
            class="{{ $containerClass }} @if($shouldAnimate) transition duration-200 ease-out motion-reduce:opacity-100! motion-reduce:transform-none! motion-reduce:transition-none! @endif"
            @if($shouldAnimate)
                {{-- Deliberately not x-show: that sets display:none until the
                     intersection observer fires, so the section collapses out of
                     the layout and everything below it jumps, twice. Measured
                     0.765 CLS on the one page whose first section is animated.
                     Toggling opacity and transform keeps the element in the
                     layout the whole time. --}}
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            @endif
        >
            {{ $slot }}
        </div>
    @else
        @if($shouldAnimate)
            <div
                class="transition duration-200 ease-out motion-reduce:opacity-100! motion-reduce:transform-none! motion-reduce:transition-none!"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    @endif
</section>
