{{--
    Logo Slider Block

    Uses shared components: x-section
    Fields: title, logos (repeater: logo, link, name), autoplay, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $logos = $fields['logos'] ?? [];
    $autoplay = $fields['autoplay'] ?? true;
    $background = $fields['background_color'] ?? 'primary';
    $uniqueId = 'logo-slider-' . uniqid();
@endphp

<x-section :background="$background" :anchor="$anchor" padding="md" class="{{ $classes }} logo-slider">
    @if($title)
        <h2 class="text-h3 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($logos))
        <div
            id="{{ $uniqueId }}"
            class="relative overflow-hidden"
            x-data="{
                logos: {{ json_encode(array_map(fn($l) => [
                    'image' => wp_get_attachment_image_src($l['logo'] ?? null, 'medium')[0] ?? '',
                    'link' => $l['link'] ?? '',
                    'name' => $l['name'] ?? ''
                ], $logos)) }},
                autoplay: {{ $autoplay ? 'true' : 'false' }},
                currentIndex: 0,
                init() {
                    if (this.autoplay) {
                        setInterval(() => {
                            this.currentIndex = (this.currentIndex + 1) % Math.max(1, this.logos.length - 4);
                        }, 3000);
                    }
                }
            }"
        >
            <div
                class="flex gap-8 transition-transform duration-500 ease-in-out"
                :style="`transform: translateX(-${currentIndex * (100 / Math.min(5, logos.length))}%)`"
            >
                @foreach($logos as $logo)
                    @php
                        $logoImage = wp_get_attachment_image_src($logo['logo'] ?? null, 'medium');
                        $link = $logo['link'] ?? '';
                        $name = $logo['name'] ?? '';
                    @endphp
                    @if($logoImage)
                        <div class="flex-shrink-0 w-1/5 px-4">
                            @if($link)
                                <a
                                    href="{{ $link }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block transition-opacity opacity-60 hover:opacity-100"
                                    @if($name) title="{{ $name }}" @endif
                                >
                                    <img
                                        src="{{ $logoImage[0] }}"
                                        alt="{{ $name }}"
                                        class="object-contain w-full h-16 grayscale hover:grayscale-0"
                                        loading="lazy"
                                    >
                                </a>
                            @else
                                <div class="opacity-60">
                                    <img
                                        src="{{ $logoImage[0] }}"
                                        alt="{{ $name }}"
                                        class="object-contain w-full h-16 grayscale"
                                        loading="lazy"
                                    >
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</x-section>
