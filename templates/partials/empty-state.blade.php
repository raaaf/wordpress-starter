{{--
    Reusable empty-state partial.

    @param string      $title      Heading text
    @param string      $text       Body text
    @param string|null $buttonLabel  Optional CTA label; omit to hide button
    @param string|null $buttonUrl    Optional CTA URL; defaults to home_url('/')
    @param string      $svgPath    SVG <path> d="" attribute for the icon
--}}

<div class="text-center py-12">
    <svg class="w-16 h-16 mx-auto text-content-tertiary mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $svgPath ?? 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' }}"/>
    </svg>
    <h2 class="text-h3 mb-4">
        {{ $title }}
    </h2>
    <p class="text-content-secondary mb-8 max-w-md mx-auto">
        {{ $text }}
    </p>
    @if (!empty($buttonLabel))
        <x-button :url="$buttonUrl ?? home_url('/')" :title="$buttonLabel" variant="primary" size="lg" />
    @endif
</div>
