{{--
    Shared post-card loop for archive.blade.php, home.blade.php and index.blade.php.

    Runs against the current global WP_Query loop (have_posts()/the_post()); the
    caller is expected to have already checked `@if (have_posts())` before
    including this partial, and to keep its page header and pagination include
    outside of it (they differ per template: aria-label, description text).

    @param string $bentoAspect   Thumbnail aspect ratio class for the bento cards.
                                  Default 'aspect-[16/10]' (archive/index); home
                                  passes 'aspect-[2/1]'.
    @param bool   $wrapSections  Wrap the featured post and the bento grid each in
                                  their own `mb-16` div (home's spacing) instead of
                                  the margin classes archive/index apply directly
                                  (`mb-12` on the article, `mb-6` on the grid).
                                  Default false.
    @param bool   $standardGrid  Render the "remaining posts" image grid used by
                                  archive/index. Default true; home passes false
                                  and renders its own text-only list afterwards.
--}}
@php
    $bentoAspect ??= 'aspect-[16/10]';
    $wrapSections ??= false;
    $standardGrid ??= true;
@endphp

{{-- FEATURED POST (first/newest post) --}}
@php the_post(); @endphp
@if ($wrapSections)
    <div class="mb-16">
        <article>
@else
    <article class="mb-12">
@endif
        <x-card variant="filled" hoverable padding="none" class="group relative overflow-hidden">
            <div class="grid md:grid-cols-2">
                {{-- Image --}}
                <div class="aspect-[4/3] md:aspect-auto md:min-h-[400px] overflow-hidden">
                    @if (has_post_thumbnail())
                        {!! get_the_post_thumbnail(null, 'large', [
                            'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
                            'loading' => 'eager',
                        ]) !!}
                    @else
                        <div class="w-full h-full bg-surface-tertiary flex items-center justify-center">
                            <x-icon name="eye" class="w-16 h-16 text-content-tertiary" />
                        </div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="p-8 md:p-12 flex flex-col justify-center">
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if (has_category())
                            @php $firstCategory = get_the_category()[0]; @endphp
                            <x-badge variant="brand">{{ $firstCategory->name }}</x-badge>
                        @endif
                        <x-badge variant="gray" style="outline">{{ get_reading_time() }}</x-badge>
                    </div>

                    <h2 class="mb-4 transition-colors group-hover:text-content-brand">
                        {{ get_the_title() }}
                    </h2>

                    <p class="text-content-secondary mb-6 line-clamp-3">
                        {{ wp_trim_words(get_the_excerpt(), 30) }}
                    </p>

                    <div class="flex items-center justify-between mt-auto">
                        <x-link :url="get_permalink()" iconRight="chevron-right" aria-hidden="true" tabindex="-1" class="relative z-20">
                            {{ __('Weiterlesen', 'wp-starter') }}
                        </x-link>
                        <time datetime="{{ get_the_date('c') }}" class="text-sm text-content-tertiary">
                            {{ get_the_date() }}
                        </time>
                    </div>
                </div>
            </div>

            {{-- Stretched link --}}
            <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                <span class="sr-only">{{ get_the_title() }}</span>
            </a>
        </x-card>
@if ($wrapSections)
        </article>
    </div>
@else
    </article>
@endif

{{-- BENTO GRID: Posts 2-3 (medium cards) --}}
@if (have_posts())
@if ($wrapSections)
    <div class="mb-16">
@endif
        <div class="grid md:grid-cols-2 gap-6{{ $wrapSections ? '' : ' mb-6' }}">
            @for ($i = 0; $i < 2 && have_posts(); $i++)
                @php the_post(); @endphp
                <article>
                    <x-card variant="filled" hoverable padding="none" class="group relative h-full">
                        @if (has_post_thumbnail())
                            <div class="{{ $bentoAspect }} overflow-hidden">
                                {!! get_the_post_thumbnail(null, 'card-video', [
                                    'class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105',
                                    'loading' => 'lazy',
                                ]) !!}
                            </div>
                        @endif

                        <div class="p-6">
                            <div class="flex flex-wrap gap-2 mb-3">
                                @if (has_category())
                                    @php $firstCategory = get_the_category()[0]; @endphp
                                    <x-badge variant="brand" size="sm">{{ $firstCategory->name }}</x-badge>
                                @endif
                                <x-badge variant="gray" style="outline" size="sm">{{ get_reading_time() }}</x-badge>
                            </div>

                            <h3 class="text-h4 mb-3 transition-colors group-hover:text-content-brand">
                                {{ get_the_title() }}
                            </h3>

                            <p class="text-content-secondary line-clamp-2 mb-4">
                                {{ wp_trim_words(get_the_excerpt(), 20) }}
                            </p>

                            <div class="flex items-center justify-between pt-4 border-t border-line">
                                <x-link :url="get_permalink()" iconRight="chevron-right" size="sm" aria-hidden="true" tabindex="-1" class="relative z-20">
                                    {{ __('Weiterlesen', 'wp-starter') }}
                                </x-link>
                                <time datetime="{{ get_the_date('c') }}" class="text-sm text-content-tertiary">
                                    {{ get_the_date() }}
                                </time>
                            </div>
                        </div>

                        {{-- Stretched link --}}
                        <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                            <span class="sr-only">{{ get_the_title() }}</span>
                        </a>
                    </x-card>
                </article>
            @endfor
        </div>
@if ($wrapSections)
    </div>
@endif
@endif

@if ($standardGrid)
    {{-- STANDARD GRID: Remaining posts --}}
    @if (have_posts())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @while (have_posts())
                @php the_post(); @endphp
                <article>
                    <x-card variant="filled" hoverable padding="none" class="group relative h-full">
                        @if (has_post_thumbnail())
                            <div class="aspect-video overflow-hidden">
                                {!! get_the_post_thumbnail(null, 'card-video', [
                                    'class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105',
                                    'loading' => 'lazy',
                                ]) !!}
                            </div>
                        @endif

                        <div class="p-5">
                            <div class="flex flex-wrap gap-2 mb-2">
                                @if (has_category())
                                    @php $firstCategory = get_the_category()[0]; @endphp
                                    <x-badge variant="brand" size="sm">{{ $firstCategory->name }}</x-badge>
                                @endif
                                <x-badge variant="gray" style="outline" size="sm">{{ get_reading_time() }}</x-badge>
                            </div>

                            <h3 class="text-h5 mb-2 transition-colors group-hover:text-content-brand line-clamp-2">
                                {{ get_the_title() }}
                            </h3>

                            <p class="text-sm text-content-secondary line-clamp-2 mb-3">
                                {{ wp_trim_words(get_the_excerpt(), 15) }}
                            </p>

                            <div class="flex items-center justify-between text-sm text-content-tertiary pt-3 border-t border-line">
                                <time datetime="{{ get_the_date('c') }}">{{ get_the_date() }}</time>
                            </div>
                        </div>

                        {{-- Stretched link --}}
                        <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                            <span class="sr-only">{{ get_the_title() }}</span>
                        </a>
                    </x-card>
                </article>
            @endwhile
        </div>
    @endif
@endif
