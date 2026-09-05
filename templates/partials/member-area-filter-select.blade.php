{{--
    Downloads-Tabelle: Kategorie-/Dateityp-Filter.

    Die Optionen kommen aus Alpine x-for ueber dynamische Facetten (Wert und
    Anzahl stehen erst zur Laufzeit fest, nicht als PHP-Array); <x-select>
    rendert seine <option>-Liste aus einem statischen PHP-Array und kann das
    nicht abbilden, deshalb hier bewusst rohes <select>-Markup statt der
    Komponente.

    @param string $xModel      Alpine x-model target, z.B. "category"
    @param string $optionsVar  Alpine-Ausdruck, ueber den x-for iteriert, z.B. "categories"
    @param string $ariaLabel   aria-label des <select>
    @param string $placeholder Text der leeren Option
    @param string $valueExpr   Ausdruck fuer :value je Option, z.B. "opt.slug"
    @param string $textExpr   Ausdruck fuer x-text je Option, z.B. "opt.label + ' (' + opt.count + ')'"
    @param string $keyExpr    Ausdruck fuer :key je Option, z.B. "opt.slug"
--}}
<div class="select relative">
    <select
        x-model="{{ $xModel }}"
        aria-label="{{ $ariaLabel }}"
        class="w-full border bg-surface-secondary text-content appearance-none cursor-pointer transition-[color,background,border-color] duration-200 h-10 text-base pl-4 pr-10 rounded-[var(--input-md-radius)] border-line-control hover:border-line-strong focus:border-line-focus focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
    >
        <option value="">{{ $placeholder }}</option>
        <template x-for="opt in {{ $optionsVar }}" :key="{{ $keyExpr }}">
            <option :value="{{ $valueExpr }}" x-text="{{ $textExpr }}"></option>
        </template>
    </select>
    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-icon-secondary">
        <x-icon name="chevron-down" class="w-4 h-4" />
    </div>
</div>
