{{--
    Rotating Chevron Component

    x-icon rendert flaechig, nicht als Outline, und nimmt keine :class-Bindung
    an (feste @props, kein Attribut-Merge). Die Rotation sitzt deshalb auf
    einem Wrapper-Span, nicht auf dem Icon selbst. Dieser Wrapper stand vorher
    dreimal fast identisch in accordion.blade.php, inline-accordion.blade.php
    und styleguide-nav.blade.php, mit leicht abweichender Grösse und Dauer.

    @param string $active - Alpine-Ausdruck, der true wird, wenn gedreht werden soll
    @param string $size - an x-icon durchgereicht (default: md, siehe icon.blade.php)
    @param string $class - zusaetzliche Klassen am Wrapper-Span

    Usage:
    <x-rotating-chevron active="active === {{ $index }}" />
    <x-rotating-chevron active="open" size="sm" />
--}}

@props([
    'active',
    'size' => 'md',
    'class' => '',
])

<span class="inline-block transition-transform duration-[var(--motion-enter-duration)] ease-[var(--motion-enter-ease)] {{ $class }}"
      :class="{ 'rotate-180': {{ $active }} }">
    <x-icon name="chevron-down" :size="$size" />
</span>
