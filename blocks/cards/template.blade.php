{{--
    Cards / Features Block

    Uses shared components: x-section, x-grid, x-card
    Fields: title, cards (repeater: icon, title, content, link), columns, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $cards = $fields['cards'] ?? [];
    $columns = $fields['columns'] ?? '3';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} cards">
    @if($title)
        <h2 class="mb-12 text-3xl font-bold text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($cards))
        <x-grid :cols="$columns" gap="lg">
            @foreach($cards as $card)
                <x-card variant="elevated" padding="lg" class="h-full">
                    {{-- Icon --}}
                    @if(!empty($card['icon']))
                        @php
                            $iconImage = wp_get_attachment_image_src($card['icon'], 'thumbnail');
                        @endphp
                        @if($iconImage)
                            <div class="flex items-center justify-center w-16 h-16 mb-6 rounded-lg bg-surface-brand-subtle">
                                <img
                                    src="{{ $iconImage[0] }}"
                                    alt=""
                                    class="w-10 h-10"
                                >
                            </div>
                        @endif
                    @endif

                    {{-- Title --}}
                    @if(!empty($card['title']))
                        <h3 class="mb-3 text-xl font-semibold text-content">{{ $card['title'] }}</h3>
                    @endif

                    {{-- Content --}}
                    @if(!empty($card['content']))
                        <p class="mb-4 text-content-secondary">{{ $card['content'] }}</p>
                    @endif

                    {{-- Link --}}
                    @if(!empty($card['link']))
                        @php
                            $link = $card['link'];
                        @endphp
                        <a
                            href="{{ $link['url'] ?? '#' }}"
                            class="inline-flex items-center mt-auto font-medium text-content-link hover:text-content-link-hover"
                            @if(!empty($link['target'])) target="{{ $link['target'] }}" @endif
                        >
                            {{ $link['title'] ?? 'Mehr erfahren' }}
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endif
                </x-card>
            @endforeach
        </x-grid>
    @endif
</x-section>
