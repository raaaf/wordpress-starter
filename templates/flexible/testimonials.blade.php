{{--
    Testimonials - Flexible Content Layout

    Supports two data sources:
    - 'manual': Uses repeater field for page-specific testimonials
    - 'cpt': Uses Testimonial CPT for centrally managed testimonials

    Uses shared components: x-section, x-section-header, x-grid, x-card
    Fields: title, source, testimonials (repeater), columns, background_color
--}}

@php
    use WordpressStarter\PostTypes\Testimonial;

    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
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

@if(!empty($testimonials) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :background="$background" class="testimonials">
    <x-section-header :headline="$title" />

    @if(!empty($testimonials))
        <x-grid :cols="$columns" gap="lg">
            @foreach($testimonials as $testimonial)
                <x-card variant="filled" padding="lg" class="flex flex-col h-full">
                    {{-- Quote Icon --}}
                    <x-icon name="quotes" class="w-8 h-8 mb-4 text-content-brand" />

                    {{-- Quote --}}
                    <blockquote class="text-body-large italic flex-grow mb-6 text-content-secondary">
                        "{!! \WordpressStarter\Helpers\Text::lineBreaks($testimonial['quote'] ?? '') !!}"
                    </blockquote>

                    {{-- Author --}}
                    <div class="flex items-center gap-4 mt-auto">
                        @if(!empty($testimonial['image']))
                            {!! wp_get_attachment_image($testimonial['image'], 'avatar', false, [
                                'alt' => \WordpressStarter\Helpers\Text::imageAlt((int) $testimonial['image'], $testimonial['author'] ?? ''),
                                'class' => 'object-cover w-12 h-12 rounded-full',
                                'sizes' => '48px',
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
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge Kundenstimmen hinzu oder wähle eine Quelle mit Einträgen.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
