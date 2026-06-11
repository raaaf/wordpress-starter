@php
    // Get header CTA options
    $showCta = \WordpressStarter\Acf\Fields::option('header_cta_show');
    $headerCta = \WordpressStarter\Acf\Fields::option('header_cta');
@endphp

<div x-data="navigation" x-init="init()" @keydown.window="handleKeydown($event)" class="relative">
    <div class="flex items-center justify-between py-6">
        {{-- Left side: Logo + Desktop Navigation --}}
        <div class="flex items-center gap-8">
            @php
                // Try ACF option first, then Customizer, then default
                $acf_logo = \WordpressStarter\Acf\Fields::option('site_logo');
                $logo_id   = null;

                if (!empty($acf_logo['ID'])) {
                    $logo_id = $acf_logo['ID'];
                } else {
                    $logo_id = get_theme_mod('custom_logo') ?: null;
                }
            @endphp

            <a href="{{ esc_url(get_bloginfo('url')) }}"
                class="inline-block transition-opacity duration-300 hover:opacity-75">
                @if($logo_id)
                    {!! wp_get_attachment_image($logo_id, 'logo', false, [
                        'alt'   => esc_attr(get_bloginfo('name')),
                        'class' => 'h-12 w-auto',
                        'sizes' => '(max-width: 768px) 128px, 256px',
                    ]) !!}
                @else
                    <img src="{{ esc_url(get_template_directory_uri() . '/resources/img/default-logo.png') }}"
                        alt="{{ esc_attr(get_bloginfo('name')) }}"
                        class="h-12 w-auto"
                        width="50" height="50">
                @endif
            </a>

            {{-- Desktop navigation (landmark provided by outer nav in header.blade.php) --}}
            <div class="desktop-nav hidden md:flex">
                @php(
                wp_nav_menu([
                    'container' => false,
                    'menu_class' => 'flex space-x-6',
                    'theme_location' => 'header-menu',
                    'li_class' => 'relative',
                    'fallback_cb' => false,
                    'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                ])
            )
            </div>
        </div>

        {{-- Mobile menu button - visible below md --}}
        <button @click="toggle()"
                data-nav-toggle
                class="md:hidden p-2 rounded-[var(--button-md-radius)] hover:bg-surface-secondary focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring-ghost)] transition-all duration-200"
                :aria-label="isOpen ? '{{ __('Menü schließen', 'wp-starter') }}' : '{{ __('Menü öffnen', 'wp-starter') }}'"
                :aria-expanded="isOpen"
                aria-haspopup="true"
                aria-controls="mobile-navigation">
            {{-- Hamburger icon --}}
            <svg x-show="!isOpen" class="w-6 h-6 text-content" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            {{-- Close icon --}}
            <span x-show="isOpen" x-cloak>
                <x-icon name="close" size="xl" class="text-content" />
            </span>
        </button>

        {{-- Desktop CTA - visible at md and above --}}
        @if($showCta && $headerCta && !empty($headerCta['url']))
            <div class="hidden md:block">
                <x-button
                    :url="$headerCta['url']"
                    :title="$headerCta['title'] ?: __('Kontakt', 'wp-starter')"
                    :target="$headerCta['target'] ?? '_self'"
                    variant="primary"
                    size="sm"
                />
            </div>
        @endif
    </div>

    {{-- Mobile navigation --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         @click.away="close()"
         @keydown="trapFocus($event)"
         class="mobile-nav-container absolute top-full left-0 right-0 bg-surface shadow-lg rounded-md md:hidden z-50"
         x-cloak>
        <nav id="mobile-navigation" class="mobile-nav" aria-label="{{ __('Mobile Navigation', 'wp-starter') }}">
            @php(
            wp_nav_menu([
                'container' => false,
                'menu_class' => 'py-2',
                'theme_location' => 'header-menu',
                'li_class' => 'relative px-4 py-2 hover:bg-surface-secondary',
                'fallback_cb' => false,
                'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            ])
        )
        </nav>

        @if($showCta && $headerCta && !empty($headerCta['url']))
            <div class="px-4 py-4 border-t border-line">
                <x-button
                    :url="$headerCta['url']"
                    :title="$headerCta['title'] ?: __('Kontakt', 'wp-starter')"
                    :target="$headerCta['target'] ?? '_self'"
                    variant="primary"
                    size="md"
                    class="w-full justify-center"
                />
            </div>
        @endif
    </div>
</div>
