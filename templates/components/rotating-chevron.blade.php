{{--
    Rotating Chevron Component

    x-icon rendert flaechig, nicht als Outline, und nimmt keine :class-Bindung
    an (feste @props, kein Attribut-Merge). Die Rotation sitzt deshalb auf
    einem Wrapper-Span, nicht auf dem Icon selbst. Dieser Wrapper stand vorher
    dreimal fast identisch in accordion.blade.php, inline-accordion.blade.php
    und styleguide-nav.blade.php, mit leicht abweichender Grösse und Dauer.

    @param string $active - Alpine-Ausdruck, der true wird, wenn gedreht werden soll.
           MUSS ein von der Entwicklung geschriebenes Literal sein (Variablenname,
           Vergleich, Funktionsaufruf) und darf NIE aus Feld-/Nutzerdaten stammen:
           der Wert landet ungefiltert in einem Alpine-Ausdruck.
    @param string $size - an x-icon durchgereicht (default: 'md', siehe icon.blade.php)
    @param string $class - zusaetzliche Klassen am Wrapper-Span (default: '')

    Usage:
    <x-rotating-chevron active="active === {{ $index }}" />
    <x-rotating-chevron active="open" size="sm" />
--}}

@props([
    'active',
    'size' => 'md',
    'class' => '',
])

@php
    // Defense-in-depth: $active must stay a literal Alpine expression, matched
    // against a closed grammar instead of a denylist (a denylist can always be
    // bypassed by a token it did not anticipate, e.g. a "]" placed between two
    // parens, or "==" stripped out before a bare-"=" check runs). Allowed,
    // exactly:
    //   identifier
    //   !identifier
    //   identifier(number)                e.g. "isOpen(0)"
    //   identifier === number|'string'    e.g. "active === 0"
    // identifier = [A-Za-z_$][A-Za-z0-9_$]*, with optional dotted property
    // access (foo.bar.baz). Anything outside this grammar is rejected, so
    // field/user data passed here by mistake fails loudly instead of being
    // interpolated into executable Alpine JS.
    $activeExpr = (string) $active;
    $identifier = '[A-Za-z_$][A-Za-z0-9_$]*(?:\.[A-Za-z_$][A-Za-z0-9_$]*)*';
    $number = '-?\d+(?:\.\d+)?';
    $string = "'[^'\\\\]*'";
    $pattern = '/^(?:'
        . $identifier
        . '|!' . $identifier
        . '|' . $identifier . '\(' . $number . '\)'
        . '|' . $identifier . '\s*===\s*(?:' . $number . '|' . $string . ')'
        . ')$/';

    if (preg_match($pattern, $activeExpr) !== 1) {
        throw new \InvalidArgumentException('x-rotating-chevron: $active must be a literal Alpine boolean expression, never field/user data.');
    }
@endphp

<span class="inline-block transition-transform duration-[var(--motion-enter-duration)] ease-[var(--motion-enter-ease)] {{ $class }}"
      :class="{ 'rotate-180': {{ $active }} }">
    <x-icon name="chevron-down" :size="$size" />
</span>
