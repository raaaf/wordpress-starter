{{--
    Blog Posts Flexible Content Layout

    Uses shared components: x-section, x-link, x-card
    Uses get_the_post_thumbnail() for automatic srcset/responsive images
    Fields: title, post_type, posts_per_page, category, show_excerpt, show_date, show_author, columns, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $postType = get_sub_field('post_type') ?: 'post';
    $postsPerPage = get_sub_field('posts_per_page') ?: 3;
    $category = get_sub_field('category') ?: '';
    $showExcerpt = get_sub_field('show_excerpt') ?? true;
    $showDate = get_sub_field('show_date') ?? true;
    $showAuthor = get_sub_field('show_author') ?? false;
    $columns = (int) (get_sub_field('columns') ?: 3);
    $background = get_sub_field('background_color') ?: 'primary';

    // Use explicit grid classes to ensure Tailwind includes them (same pattern as stats.blade.php)
    $gridClass = match(true) {
        $columns >= 4 => 'md:grid-cols-4',
        $columns === 3 => 'md:grid-cols-3',
        $columns === 2 => 'md:grid-cols-2',
        default => 'md:grid-cols-1',
    };

    // Query posts
    $args = [
        'post_type' => $postType,
        'posts_per_page' => $postsPerPage,
        'post_status' => 'publish',
        'no_found_rows' => true,
    ];

    if ($category) {
        $args['cat'] = $category;
    }

    $postsQuery = new WP_Query($args);
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="posts">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if($postsQuery->have_posts())
        <ul class="grid gap-8 {{ $gridClass }}" role="list">
            @while($postsQuery->have_posts())
                @php $postsQuery->the_post(); @endphp
                <li>
                    <x-card variant="filled" padding="none" hoverable class="group relative cursor-pointer h-full">
                        @if(has_post_thumbnail())
                            <div class="block overflow-hidden aspect-video">
                                {!! get_the_post_thumbnail(get_the_ID(), 'card-video', [
                                    'class' => 'object-cover w-full h-full transition-transform duration-300 group-hover:scale-105',
                                    'loading' => 'lazy',
                                ]) !!}
                            </div>
                        @endif

                        <div class="p-6">
                            @if($showDate || $showAuthor)
                                <div class="flex items-center gap-4 mb-3">
                                    @if($showDate)
                                        <x-badge variant="gray" style="outline" size="sm">
                                            <time datetime="{{ get_the_date('c') }}">
                                                {{ get_the_date('j. F Y') }}
                                            </time>
                                        </x-badge>
                                    @endif
                                    @if($showAuthor)
                                        <span class="text-body-small text-content-secondary">{{ __('von', 'wp-starter') }} {{ get_the_author() }}</span>
                                    @endif
                                </div>
                            @endif

                            <h3 class="text-h4 mb-3 transition-colors duration-200 group-hover:text-content-brand">
                                {{ get_the_title() }}
                            </h3>

                            @if($showExcerpt)
                                <p class="mb-4 text-content-secondary line-clamp-3">
                                    {{ wp_trim_words(get_the_excerpt(), 20) }}
                                </p>
                            @endif

                            <x-link :url="get_permalink()" iconRight="chevron-right" aria-hidden="true" tabindex="-1" class="relative z-20 group-hover:text-content-brand!">{{ __('Weiterlesen', 'wp-starter') }}</x-link>
                        </div>

                        {{-- Stretched link covering entire card (z-10, below the Weiterlesen link at z-20) --}}
                        <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                            <span class="sr-only">{{ get_the_title() }}</span>
                        </a>
                    </x-card>
                </li>
            @endwhile
        </ul>
        @php wp_reset_postdata(); @endphp
    @else
        <p class="text-center text-content-secondary">{{ __('Keine Beiträge gefunden.', 'wp-starter') }}</p>
    @endif
</x-section>
