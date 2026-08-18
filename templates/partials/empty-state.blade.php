{{--
    Reusable empty-state partial.

    @param string      $title      Heading text
    @param string      $text       Body text
    @param string|null $buttonLabel  Optional CTA label; omit to hide button
    @param string|null $buttonUrl    Optional CTA URL; defaults to home_url('/')
    @param string      $icon       Icon name from the theme's icon set (default: archive)
--}}

<div class="text-center py-12">
    <x-icon name="{{ $icon ?? 'archive' }}" class="w-16 h-16 mx-auto text-content-tertiary mb-6" />
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
