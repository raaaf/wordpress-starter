{{--
    Cards / Features Block

    Uses shared components: x-section, x-grid, x-card, x-link
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
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
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
                        <h3 class="text-h4 mb-3 text-content">{{ $card['title'] }}</h3>
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
                        <x-link
                            :url="$link['url'] ?? '#'"
                            :target="$link['target'] ?? '_self'"
                            iconRight="chevron"
                            class="mt-auto"
                        >
                            {{ $link['title'] ?? 'Mehr erfahren' }}
                        </x-link>
                    @endif
                </x-card>
            @endforeach
        </x-grid>
    @endif
</x-section>
