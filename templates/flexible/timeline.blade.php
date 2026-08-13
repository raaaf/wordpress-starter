{{--
    Timeline Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-badge, x-card
    Fields: title, events (repeater: year, title, content, image), background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $events = get_sub_field('events') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if(!empty($events) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :background="$background" class="timeline">
    <x-section-header :headline="$title" />

    @if(!empty($events))
        <div class="relative">
            {{-- Vertical line (decorative) --}}
            <div class="absolute hidden w-0.5 h-full transform -translate-x-1/2 md:block bg-line left-1/2" aria-hidden="true"></div>

            <ol class="space-y-12 list-none p-0 m-0">
                @foreach($events as $index => $event)
                    @php
                        $year = $event['year'] ?? '';
                        $eventTitle = $event['title'] ?? '';
                        $content = $event['content'] ?? '';
                        $imageId = $event['image'] ?? null;
                        $isEven = $index % 2 === 0;
                    @endphp
                    <li class="relative flex flex-col md:flex-row {{ $isEven ? '' : 'md:flex-row-reverse' }} items-center gap-8">
                        {{-- Timeline dot (decorative) --}}
                        <div class="absolute z-10 hidden w-4 h-4 transform -translate-x-1/2 rounded-full md:block bg-surface-brand left-1/2" aria-hidden="true"></div>

                        {{-- Content card --}}
                        <div class="w-full md:w-[calc(50%-2rem)] md:text-left">
                            <x-card variant="filled" padding="lg">
                                @if($year)
                                    <x-badge variant="accent" size="md" class="mb-3">{{ $year }}</x-badge>
                                @endif

                                @if($eventTitle)
                                    <h3 class="text-h4 mb-2">{{ $eventTitle }}</h3>
                                @endif

                                @if($content)
                                    <div class="prose text-content-secondary">
                                        @kses($content)
                                    </div>
                                @endif

                                @if($imageId)
                                    {!! wp_get_attachment_image($imageId, 'gallery-thumb', false, [
                                        'class' => 'mt-4 rounded-lg',
                                        'loading' => 'lazy',
                                        'sizes' => '(max-width: 768px) 100vw, 50vw',
                                        'alt' => $eventTitle,
                                    ]) !!}
                                @endif
                            </x-card>
                        </div>

                        {{-- Spacer for other side --}}
                        <div class="hidden md:block md:w-[calc(50%-2rem)]"></div>
                    </li>
                @endforeach
            </ol>
        </div>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge mindestens ein Ereignis hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
