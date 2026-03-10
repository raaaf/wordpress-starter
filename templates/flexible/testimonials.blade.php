{{--
    Testimonials - Flexible Content Layout

    Supports two data sources:
    - 'manual': Uses repeater field for page-specific testimonials
    - 'cpt': Uses Testimonial CPT for centrally managed testimonials

    Uses shared components: x-section, x-grid, x-card
    Fields: title, source, testimonials (repeater), columns, background_color
--}}

@php
    use WordpressStarter\PostTypes\Testimonial;

    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $source = get_sub_field('source') ?: 'manual';
    $columns = get_sub_field('columns') ?: '3';
    $background = get_sub_field('background_color') ?: 'primary';

    // Normalize testimonials data from either source
    $testimonials = [];

    if ($source === 'cpt' && class_exists(Testimonial::class)) {
        // Load from CPT and normalize structure
        $cptTestimonials = Testimonial::getTestimonials();
        foreach ($cptTestimonials as $item) {
            $testimonials[] = [
                'quote' => $item['content'],
                'author' => $item['author_name'],
                'role' => $item['author_position'],
                'image' => $item['image'], // Featured Image ID
            ];
        }
    } else {
        // Use manual repeater data
        $testimonials = get_sub_field('testimonials') ?: [];
    }
@endphp

<x-section :background="$background" class="testimonials">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($testimonials))
        <x-grid :cols="$columns" gap="lg">
            @foreach($testimonials as $testimonial)
                <x-card variant="filled" padding="lg" class="flex flex-col h-full">
                    {{-- Quote Icon --}}
                    <svg class="w-8 h-8 mb-4 text-content-brand" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>

                    {{-- Quote --}}
                    <blockquote class="text-body-large italic flex-grow mb-6 text-content-secondary">
                        "{{ $testimonial['quote'] ?? '' }}"
                    </blockquote>

                    {{-- Author --}}
                    <div class="flex items-center gap-4 mt-auto">
                        @if(!empty($testimonial['image']))
                            {!! wp_get_attachment_image($testimonial['image'], 'avatar', false, [
                                'class' => 'object-cover w-12 h-12 rounded-full',
                                'loading' => 'lazy',
                                'sizes' => '48px',
                                'alt' => '',
                            ]) !!}
                        @endif
                        <div>
                            <div class="font-semibold text-content">{{ $testimonial['author'] ?? '' }}</div>
                            @if(!empty($testimonial['role']))
                                <div class="text-body-small text-content-secondary">{{ $testimonial['role'] }}</div>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </x-grid>
    @endif
</x-section>
