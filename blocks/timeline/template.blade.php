{{--
    Timeline Block

    Uses shared components: x-section
    Fields: title, events (repeater: year, title, content, image), background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $events = $fields['events'] ?? [];
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} timeline-block">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($events))
        <div class="relative">
            {{-- Vertical line --}}
            <div class="absolute hidden w-0.5 h-full transform -translate-x-1/2 md:block bg-line left-1/2"></div>

            <div class="space-y-12">
                @foreach($events as $index => $event)
                    @php
                        $year = $event['year'] ?? '';
                        $eventTitle = $event['title'] ?? '';
                        $content = $event['content'] ?? '';
                        $image = wp_get_attachment_image_src($event['image'] ?? null, 'medium');
                        $isEven = $index % 2 === 0;
                    @endphp
                    <div class="relative flex flex-col md:flex-row {{ $isEven ? '' : 'md:flex-row-reverse' }} items-center gap-8">
                        {{-- Timeline dot --}}
                        <div class="absolute z-10 hidden w-4 h-4 transform -translate-x-1/2 rounded-full md:block bg-surface-brand left-1/2"></div>

                        {{-- Content card --}}
                        <div class="w-full md:w-[calc(50%-2rem)] {{ $isEven ? 'md:text-right' : 'md:text-left' }}">
                            <div class="p-6 rounded-xl bg-surface-secondary">
                                @if($year)
                                    <span class="inline-block px-3 py-1 mb-3 text-sm font-semibold rounded-full bg-surface-brand text-content-inverse">
                                        {{ $year }}
                                    </span>
                                @endif

                                @if($eventTitle)
                                    <h3 class="text-h4 mb-2 text-content">{{ $eventTitle }}</h3>
                                @endif

                                @if($content)
                                    <div class="prose text-content-secondary">
                                        {!! $content !!}
                                    </div>
                                @endif

                                @if($image)
                                    <img
                                        src="{{ $image[0] }}"
                                        alt="{{ $eventTitle }}"
                                        class="mt-4 rounded-lg"
                                        loading="lazy"
                                    >
                                @endif
                            </div>
                        </div>

                        {{-- Spacer for other side --}}
                        <div class="hidden md:block md:w-[calc(50%-2rem)]"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-section>
