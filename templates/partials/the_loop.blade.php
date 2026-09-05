{{--
    WP_Query post loop: iterates have_posts()/the_post() and yields $slot per
    post. Not to be confused with partials/page-sections-loop.blade.php, which
    iterates a single page's ACF flexible-content rows, not WP_Query posts.
--}}
@if(have_posts())
    @while(have_posts())
        @php(the_post())
        {{ $slot }}
    @endwhile
@endif
