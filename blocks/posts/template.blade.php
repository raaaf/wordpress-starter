{{--
    Blog Posts Block

    Uses shared components: x-section
    Fields: title, post_type, posts_per_page, category, show_excerpt, show_date, show_author, columns, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $postType = $fields['post_type'] ?? 'post';
    $postsPerPage = $fields['posts_per_page'] ?? 3;
    $category = $fields['category'] ?? '';
    $showExcerpt = $fields['show_excerpt'] ?? true;
    $showDate = $fields['show_date'] ?? true;
    $showAuthor = $fields['show_author'] ?? false;
    $columns = $fields['columns'] ?? 3;
    $background = $fields['background_color'] ?? 'primary';

    // Query posts
    $args = [
        'post_type' => $postType,
        'posts_per_page' => $postsPerPage,
        'post_status' => 'publish',
    ];

    if ($category) {
        $args['cat'] = $category;
    }

    $postsQuery = new WP_Query($args);
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} posts-block">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
    @endif

    @if($postsQuery->have_posts())
        <div class="grid gap-8 md:grid-cols-{{ $columns }}">
            @while($postsQuery->have_posts())
                @php $postsQuery->the_post(); @endphp
                <article class="overflow-hidden transition-shadow rounded-xl bg-surface-secondary hover:shadow-lg group">
                    @if(has_post_thumbnail())
                        <a href="{{ get_permalink() }}" class="block overflow-hidden aspect-video">
                            <img
                                src="{{ get_the_post_thumbnail_url(get_the_ID(), 'medium_large') }}"
                                alt="{{ get_the_title() }}"
                                class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            >
                        </a>
                    @endif

                    <div class="p-6">
                        @if($showDate || $showAuthor)
                            <div class="flex items-center gap-4 mb-3 text-body-small text-content-secondary">
                                @if($showDate)
                                    <time datetime="{{ get_the_date('c') }}">
                                        {{ get_the_date('j. F Y') }}
                                    </time>
                                @endif
                                @if($showAuthor)
                                    <span>von {{ get_the_author() }}</span>
                                @endif
                            </div>
                        @endif

                        <h3 class="text-h4 mb-3 text-content">
                            <a href="{{ get_permalink() }}" class="hover:text-content-brand">
                                {{ get_the_title() }}
                            </a>
                        </h3>

                        @if($showExcerpt)
                            <p class="mb-4 text-content-secondary line-clamp-3">
                                {{ wp_trim_words(get_the_excerpt(), 20) }}
                            </p>
                        @endif

                        <a href="{{ get_permalink() }}" class="inline-flex items-center gap-2 font-medium text-content-link hover:text-content-link-hover">
                            Weiterlesen
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </article>
            @endwhile
        </div>
        @php wp_reset_postdata(); @endphp
    @else
        <p class="text-center text-content-secondary">Keine Beiträge gefunden.</p>
    @endif
</x-section>
