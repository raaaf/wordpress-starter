@php
    // Get header CTA options
    $showCta = \WordpressStarter\Acf\Fields::option('header_cta_show');
    $headerCta = \WordpressStarter\Acf\Fields::option('header_cta');
    // Optional outlined second button (e.g. a login), left of the CTA or alone
    $headerCtaSecondary = \WordpressStarter\Acf\Fields::option('header_cta_secondary');

    $isLandingPage = \WordpressStarter\Acf\PageSettings::isLandingPage();
    $showCta = $showCta && !$isLandingPage;
    $showPrimary = $showCta && is_array($headerCta) && !empty($headerCta['url']);
    $showSecondary = $showCta && is_array($headerCtaSecondary) && !empty($headerCtaSecondary['url']);
    $showThemeSwitcher = (bool) \WordpressStarter\Acf\Fields::option('header_theme_switcher', true);
@endphp

<div x-data="navigation" @keydown.window="handleKeydown($event)" class="relative">
    <div data-nav-row class="flex items-center justify-between gap-4 min-h-[68px] py-2">
        {{-- Left side: Logo + Desktop Navigation --}}
        {{-- Below md the logo group may shrink, otherwise logo plus switcher is wider than a 320 px viewport --}}
        <div class="flex items-center gap-8 min-w-0 md:shrink-0">
            @php
                // ACF option first, then Customizer, then default (see Fields::siteLogoMarkup)
                $logoMarkup = \WordpressStarter\Acf\Fields::siteLogoMarkup('header');
            @endphp

            @if($isLandingPage)
                {{-- $logoMarkup is escaped inside Fields::siteLogoMarkup(). --}}
                <span class="inline-block">{!! $logoMarkup !!}</span>
            @else
                <a href="{{ esc_url(get_bloginfo('url')) }}"
                    class="inline-block min-w-0 md:shrink-0 transition-opacity duration-[var(--motion-enter-duration)] ease-[var(--motion-enter-ease)] hover:opacity-75">{!! $logoMarkup !!}</a>
            @endif

            {{-- Desktop navigation (landmark provided by outer nav in header.blade.php) --}}
            @unless($isLandingPage)
                <div class="desktop-nav hidden md:flex shrink-0">
                    @php(
                    wp_nav_menu([
                        'container' => false,
                        'menu_class' => 'flex gap-1',
                        'theme_location' => 'header-menu',
                        'li_class' => 'relative',
                        'fallback_cb' => false,
                        'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    ])
                )
                </div>
            @endunless
        </div>

        <div class="flex items-center gap-2 md:gap-3 shrink-0">
        {{-- Colour scheme switcher (icons), all widths; the partial renders
             nothing unless the colour scheme option follows the system. --}}
        @if($showThemeSwitcher)
            @include('partials.theme-switcher', ['compact' => true])
        @endif

        {{-- Mobile menu button - visible below md --}}
        @unless($isLandingPage)
            <button @click="toggle()"
                    data-nav-toggle
                    class="md:hidden p-2 rounded-[var(--button-md-radius)] hover:bg-surface-secondary focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] transition-all duration-200"
                    :aria-label="isOpen ? '{{ esc_js(__('Menü schließen', 'wp-starter')) }}' : '{{ esc_js(__('Menü öffnen', 'wp-starter')) }}'"
                    :aria-expanded="isOpen"

                    aria-controls="mobile-navigation">
                {{-- Both icons stay rendered and cross-fade via CSS on aria-expanded --}}
                <span class="nav-icon-stack">
                    <span class="nav-icon nav-icon--menu">
                        <x-icon name="menu" size="xl" class="text-content" />
                    </span>
                    <span class="nav-icon nav-icon--close">
                        <x-icon name="close" size="xl" class="text-content" />
                    </span>
                </span>
            </button>
        @endunless

        {{-- Desktop CTA - visible at md and above --}}
        @if($showPrimary || $showSecondary)
            <div data-nav-desktop-cta class="hidden md:flex items-center gap-3">
                @if($showSecondary)
                    <x-button
                        :url="$headerCtaSecondary['url']"
                        :title="$headerCtaSecondary['title'] ?: __('Login', 'wp-starter')"
                        :target="$headerCtaSecondary['target'] ?? '_self'"
                        variant="secondary"
                        size="sm"
                    />
                @endif
                @if($showPrimary)
                    <x-button
                        :url="$headerCta['url']"
                        :title="$headerCta['title'] ?: __('Kontakt', 'wp-starter')"
                        :target="$headerCta['target'] ?? '_self'"
                        variant="primary"
                        size="sm"
                    />
                @endif
            </div>
        @endif
        </div>
    </div>

    {{-- Mobile navigation --}}
    @unless($isLandingPage)
        <div x-show="isOpen"
             x-transition:enter="nav-panel-enter"
             x-transition:enter-start="nav-panel-from"
             x-transition:enter-end="nav-panel-to"
             x-transition:leave="nav-panel-leave"
             x-transition:leave-start="nav-panel-to"
             x-transition:leave-end="nav-panel-out"
             :class="{ 'is-open': isOpen }"
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
                    'li_class' => 'relative',
                    'fallback_cb' => false,
                    'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                ])
            )
            </nav>

            @if($showPrimary || $showSecondary)
                <div class="nav-panel-cta flex flex-col gap-3 px-4 py-4 border-t border-line">
                    @if($showSecondary)
                        <x-button
                            :url="$headerCtaSecondary['url']"
                            :title="$headerCtaSecondary['title'] ?: __('Login', 'wp-starter')"
                            :target="$headerCtaSecondary['target'] ?? '_self'"
                            variant="secondary"
                            size="md"
                            class="w-full justify-center"
                        />
                    @endif
                    @if($showPrimary)
                        <x-button
                            :url="$headerCta['url']"
                            :title="$headerCta['title'] ?: __('Kontakt', 'wp-starter')"
                            :target="$headerCta['target'] ?? '_self'"
                            variant="primary"
                            size="md"
                            class="w-full justify-center"
                        />
                    @endif
                </div>
            @endif
        </div>
    @endunless
</div>
