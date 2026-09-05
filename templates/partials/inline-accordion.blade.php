{{--
    Inline Accordion Partial

    Used by image column layouts (one-column-image, two-columns-images, three-columns-images, four-columns-images).
    Parameters:
      $items    — array of accordion items (each has 'title' and 'content')
      $idPrefix — string prefix for button/panel IDs to ensure uniqueness per layout instance
--}}

@php
    // Sanitized once, used consistently everywhere the prefix lands in
    // markup: the x-ref, the ids, and the aria-controls values.
    $safePrefix = sanitize_html_class($idPrefix);
@endphp
<div class="p-6 lg:p-8" x-data="{
    active: null,
    itemCount: {{ count($items) }},
    focusItem(index) {
        this.$nextTick(() => {
            this.$refs['{{ esc_js($safePrefix) }}' + index]?.focus();
        });
    }
}">
    @foreach($items as $aIdx => $aItem)
        <div class="border-b border-line last:border-b-0">
            <button x-ref="{{ $safePrefix }}{{ $aIdx }}"
                    id="{{ $safePrefix }}-btn-{{ $aIdx }}"
                    @click="active = active === {{ $aIdx }} ? null : {{ $aIdx }}"
                    @keydown.down.prevent="focusItem(({{ $aIdx }} + 1) % itemCount)"
                    @keydown.up.prevent="focusItem(({{ $aIdx }} - 1 + itemCount) % itemCount)"
                    @keydown.home.prevent="focusItem(0)"
                    @keydown.end.prevent="focusItem(itemCount - 1)"
                    :aria-expanded="active === {{ $aIdx }}"
                    aria-controls="{{ $safePrefix }}-{{ $aIdx }}"
                    class="group flex items-center justify-between w-full py-3 font-normal text-left cursor-pointer transition-colors hover:text-content-brand focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                    :class="{ 'text-content-brand': active === {{ $aIdx }} }">
                {{ $aItem['title'] }}
                <x-rotating-chevron active="active === {{ $aIdx }}" class="shrink-0" />
            </button>
            {{-- Dauer auf 200ms gesetzt statt des unkonfigurierten 250ms-Standards
                 des Collapse-Plugins: so läuft das Panel synchron mit dem Chevron
                 (transition-transform duration-200 oben), passend zum Enter-Token
                 --motion-enter-duration der Motion-Skala. --}}
            <div x-show="active === {{ $aIdx }}"
                 x-collapse.duration.200ms
                 id="{{ $safePrefix }}-{{ $aIdx }}"
                 {{-- role=region nur unter 7 Eintraegen: darueber ueberladet jeder
                      Eintrag die Landmark-Liste der Screenreader-Navigation
                      (gleiche Schwelle wie templates/flexible/accordion.blade.php). --}}
                 @if(count($items) < 7) role="region" @endif
                 aria-labelledby="{{ $safePrefix }}-btn-{{ $aIdx }}"
                 class="pb-4">
                <x-prose class="text-sm">@kses($aItem['content'])</x-prose>
            </div>
        </div>
    @endforeach
</div>
