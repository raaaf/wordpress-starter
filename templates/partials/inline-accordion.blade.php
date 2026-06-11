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
                    class="group flex items-center justify-between w-full py-3 font-bold text-left cursor-pointer transition-colors hover:text-content-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                    :class="{ 'text-content-brand': active === {{ $aIdx }} }">
                {{ $aItem['title'] }}
                <svg class="w-4 h-4 shrink-0 transition-transform duration-200"
                     :class="{ 'rotate-180': active === {{ $aIdx }} }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="active === {{ $aIdx }}"
                 x-collapse
                 id="{{ $idPrefix }}-{{ $aIdx }}"
                 role="region"
                 aria-labelledby="{{ $idPrefix }}-btn-{{ $aIdx }}"
                 class="pb-4">
                <x-prose class="text-sm">@kses($aItem['content'])</x-prose>
            </div>
        </div>
    @endforeach
</div>
