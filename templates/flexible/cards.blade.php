{{--
    Cards / Features - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-card, x-link
    Fields: title, cards (repeater: icon, title, content, link), columns, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $cards = get_sub_field('cards');
    $columns = get_sub_field('columns') ?: '3';
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="cards">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($cards))
        <x-grid :cols="$columns" gap="lg">
            @foreach($cards as $card)
                <x-card variant="elevated" padding="lg" class="h-full">
                    {{-- Icon --}}
                    @if(!empty($card['icon']))
                        <div class="flex items-center justify-center w-16 h-16 mb-6 rounded-lg bg-surface-brand-subtle text-content-brand">
                            <x-icon :name="$card['icon']" size="xl" />
                        </div>
                    @endif

                    {{-- Title --}}
                    @if(!empty($card['title']))
                        <h3 class="text-h4 mb-3">{{ $card['title'] }}</h3>
                    @endif

                    {{-- Content --}}
                    @if(!empty($card['content']))
                        <p class="mb-4 text-content-secondary">{!! \WordpressStarter\Helpers\Text::lineBreaks($card['content']) !!}</p>
                    @endif

                    {{-- Link --}}
                    @if(!empty($card['link']))
                        @php
                            $link = $card['link'];
                        @endphp
                        <x-link
                            :url="$link['url'] ?? '#'"
                            :target="$link['target'] ?? '_self'"
                            :ariaLabel="($link['title'] ?? __('Mehr erfahren', 'wp-starter')) . (!empty($card['title']) ? ': ' . $card['title'] : '')"
                            class="mt-auto"
                        >
                            {{ $link['title'] ?? __('Mehr erfahren', 'wp-starter') }}
                        </x-link>
                    @endif
                </x-card>
            @endforeach
        </x-grid>
    @endif
</x-section>
