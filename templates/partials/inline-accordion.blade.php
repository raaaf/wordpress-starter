{{--
    Inline Accordion Partial

    Used by image column layouts (one-column-image, two-columns-images, three-columns-images, four-columns-images).
    Parameters:
      $items    — array of accordion items (each has 'title' and 'content')
      $idPrefix — string prefix for button/panel IDs to ensure uniqueness per layout instance
--}}

<div class="p-6 lg:p-8" x-data="{ active: null }">
    @foreach($items as $aIdx => $aItem)
        <div class="border-b border-line last:border-b-0">
            <button id="{{ $idPrefix }}-btn-{{ $aIdx }}"
                    @click="active = active === {{ $aIdx }} ? null : {{ $aIdx }}"
                    :aria-expanded="active === {{ $aIdx }}"
                    aria-controls="{{ $idPrefix }}-{{ $aIdx }}"
                    class="group flex items-center justify-between w-full py-3 font-bold text-left cursor-pointer transition-colors hover:text-content-brand focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring-ghost)]"
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
                 id="{{ $idPrefix }}-{{ $aIdx }}"
                 role="region"
                 aria-labelledby="{{ $idPrefix }}-btn-{{ $aIdx }}"
                 class="pb-4">
                <x-prose class="text-sm">@kses($aItem['content'])</x-prose>
            </div>
        </div>
    @endforeach
</div>
