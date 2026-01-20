@include('partials.header')
<main id="main-content">
    @yield('content')
</main>

@if (defined('WP_DEBUG') && WP_DEBUG)
    <div class="fixed p-2 text-xs text-content-inverse bg-surface-inverse rounded shadow bottom-2 right-2">
        <span class="sm:hidden">xs</span>
        <span class="hidden sm:inline md:hidden">sm</span>
        <span class="hidden md:inline lg:hidden">md</span>
        <span class="hidden lg:inline xl:hidden">lg</span>
        <span class="hidden xl:inline 2xl:hidden">xl</span>
        <span class="hidden 2xl:inline">2xl</span>
    </div>
@endif

@include('partials.footer')
