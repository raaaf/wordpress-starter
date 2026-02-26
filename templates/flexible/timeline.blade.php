{{--
    Timeline Flexible Content Layout

    Uses shared components: x-section, x-badge, x-card
    Fields: title, events (repeater: year, title, content, image), background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $events = get_sub_field('events') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="timeline">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($events))
        <div class="relative">
            {{-- Vertical line (decorative) --}}
            <div class="absolute hidden w-0.5 h-full transform -translate-x-1/2 md:block bg-line left-1/2" aria-hidden="true"></div>

            <div class="space-y-12">
                @foreach($events as $index => $event)
                    @php
                        $year = $event['year'] ?? '';
                        $eventTitle = $event['title'] ?? '';
                        $content = $event['content'] ?? '';
                        $image = wp_get_attachment_image_src($event['image'] ?? null, 'gallery-thumb');
                        $isEven = $index % 2 === 0;
                    @endphp
                    <div class="relative flex flex-col md:flex-row {{ $isEven ? '' : 'md:flex-row-reverse' }} items-center gap-8">
                        {{-- Timeline dot (decorative) --}}
                        <div class="absolute z-10 hidden w-4 h-4 transform -translate-x-1/2 rounded-full md:block bg-surface-brand left-1/2" aria-hidden="true"></div>

                        {{-- Content card --}}
                        <div class="w-full md:w-[calc(50%-2rem)] {{ $isEven ? 'md:text-right' : 'md:text-left' }}">
                            <x-card variant="filled" padding="lg" class="rounded-xl">
                                @if($year)
                                    <x-badge variant="accent" size="md" class="mb-3">{{ $year }}</x-badge>
                                @endif

                                @if($eventTitle)
                                    <h3 class="text-h4 mb-2">{{ $eventTitle }}</h3>
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
                            </x-card>
                        </div>

                        {{-- Spacer for other side --}}
                        <div class="hidden md:block md:w-[calc(50%-2rem)]"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-section>
