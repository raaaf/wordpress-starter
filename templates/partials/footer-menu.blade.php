@php
    // Get footer options - Info column
    $showLogo = \WordpressStarter\Acf\Fields::option('footer_show_logo', true);
    $showCompany = \WordpressStarter\Acf\Fields::option('footer_show_company', true);
    $footerText = \WordpressStarter\Acf\Fields::option('footer_text', '');

    $reduceFooter = \WordpressStarter\Acf\PageSettings::shouldReduceFooter();
    $isLandingPage = \WordpressStarter\Acf\PageSettings::isLandingPage();

    // Navigation column
    $navMenu = \WordpressStarter\Acf\Fields::option('footer_nav_menu', 'footer-menu');
    // Ohne zugewiesenes Menü blieben sonst eine leere Überschrift und ein leeres nav-Landmark stehen.
    $showNav = \WordpressStarter\Acf\Fields::option('footer_show_nav', true) && has_nav_menu($navMenu) && !$isLandingPage;
    $navTitle = \WordpressStarter\Acf\Fields::option('footer_nav_title') ?: __('Navigation', 'wp-starter');

    // Contact column
    $showContact = \WordpressStarter\Acf\Fields::option('footer_show_contact', true);
    $contactTitle = \WordpressStarter\Acf\Fields::option('footer_contact_title') ?: __('Kontakt', 'wp-starter');

    // Social column
    $showSocial = \WordpressStarter\Acf\Fields::option('footer_show_social', true) && !$isLandingPage;
    $socialTitle = \WordpressStarter\Acf\Fields::option('footer_social_title') ?: __('Folge uns', 'wp-starter');

    // Bottom bar
    $defaultCopyright = __('© {year} Firmenname. Alle Rechte vorbehalten.', 'wp-starter');
    $copyrightText = \WordpressStarter\Acf\Fields::option('copyright_text') ?: $defaultCopyright;
    // Ohne zugewiesenes Menü blieben sonst eine leere Überschrift und ein leeres nav-Landmark stehen.
    $showLegal = \WordpressStarter\Acf\Fields::option('footer_show_legal', true) && has_nav_menu('legal-menu');

    // Get contact info from general settings
    $company = \WordpressStarter\Acf\Fields::option('company_name', '');
    $address = \WordpressStarter\Acf\Fields::option('address', '');
    $phone = \WordpressStarter\Acf\Fields::option('phone', '');
    $fax = \WordpressStarter\Acf\Fields::option('fax', '');
    $email = \WordpressStarter\Acf\Fields::option('email', '');

    // Get social links
    $socialLinks = \WordpressStarter\Acf\Fields::option('social_links', []);

    // Replace {year} placeholder
    $copyrightText = str_replace('{year}', wp_date('Y'), $copyrightText);

    // Footer style: 'simple' is two compact rows (brand and contact; copyright and legal links), anything else the column footer
    $isSimple = \WordpressStarter\Acf\Fields::option('footer_style', 'default') === 'simple';
@endphp

@if($isSimple)
{{-- Simple footer: two rows on desktop (brand and contact; copyright and legal links), stacked on mobile. --}}
@php
    // The address is one line here, so its line breaks become commas.
    $simpleAddress = implode(', ', array_filter(array_map('trim', preg_split('/\R/', (string) $address) ?: [])));
    $showBrand = !$reduceFooter && ($showLogo || ($showCompany && $company));
    $showSimpleContact = !$reduceFooter && $showContact && ($simpleAddress || $phone || $fax || $email);
@endphp
<footer class="footer--simple bg-surface-tertiary">
    <div class="max-w-[var(--container-max)] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-5">
        @if($showBrand || $showSimpleContact)
            {{-- Row 1: brand, then all contact details in one line. --}}
            <div class="flex flex-col lg:flex-row lg:items-center gap-x-10 gap-y-4 mb-4">
                @if($showBrand)
                    {{-- flex: no baseline gap below the logo, so the space above the hairline is exactly 16 px --}}
                    <div class="shrink-0 flex">
                        @if($showLogo)
                            @php
                                // Same fallback chain as the header, smaller; escaped inside Fields::siteLogoMarkup.
                                $footerLogoMarkup = \WordpressStarter\Acf\Fields::siteLogoMarkup('footer-simple');
                            @endphp
                            @if($isLandingPage)
                                <span class="flex">{!! $footerLogoMarkup !!}</span>
                            @else
                                <a href="{{ esc_url(home_url('/')) }}" class="flex">{!! $footerLogoMarkup !!}</a>
                            @endif
                        @else
                            <p class="text-h5 mt-0 text-content mb-0">{{ $company }}</p>
                        @endif
                    </div>
                @endif

                @if($showSimpleContact)
                    <address class="not-italic text-content-secondary text-sm flex flex-col lg:flex-row lg:flex-wrap items-start gap-x-6 gap-y-1">
                        @if($simpleAddress)
                            <span>{{ $simpleAddress }}</span>
                        @endif
                        @if($phone)
                            <a href="tel:{{ \WordpressStarter\Helpers\Text::telHref($phone) }}" class="hover:text-content transition-colors">{{ $phone }}</a>
                        @endif
                        @if($fax)
                            <span>{{ __('Telefax', 'wp-starter') }}: {{ $fax }}</span>
                        @endif
                        @if($email)
                            <a href="{{ esc_url('mailto:' . $email) }}" class="hover:text-content transition-colors">{{ $email }}</a>
                        @endif
                    </address>
                @endif
            </div>
        @endif

        {{-- Row 2: hairline, then copyright on the left axis and legal links on the right, more muted than the contact row. --}}
        <div class="flex flex-col-reverse lg:flex-row lg:items-center lg:justify-between gap-x-8 gap-y-2 @if($showBrand || $showSimpleContact) border-t border-line pt-4 @endif">
            <p class="text-content-tertiary text-sm mb-0">{{ $copyrightText }}</p>

            @if($showLegal)
                <nav class="legal-nav" aria-label="{{ __('Rechtliche Links', 'wp-starter') }}">
                    <?php
                    wp_nav_menu([
                        'container' => false,
                        'menu_class' => 'flex flex-wrap gap-x-6 gap-y-1 text-sm',
                        'theme_location' => 'legal-menu',
                        'fallback_cb' => false,
                        'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    ]);
                    ?>
                </nav>
            @endif
        </div>
    </div>
</footer>
@else
<footer class="bg-surface-tertiary">
    @if(!$reduceFooter)
    <div class="max-w-[var(--container-max)] mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 {{ $isLandingPage ? 'lg:grid-cols-2' : 'lg:grid-cols-4' }} gap-8 lg:gap-12">
            {{-- Logo / Company Info / Footer Text --}}
            <div class="lg:col-span-1">
                @if($showLogo)
                    @php
                        // Same fallback chain as the header (custom logo, Customizer, text
                        // fallback), see Fields::siteLogoMarkup. Escaped inside that method.
                        $footerLogoMarkup = \WordpressStarter\Acf\Fields::siteLogoMarkup('footer');
                    @endphp
                    @if($isLandingPage)
                        <span class="inline-block mb-4">{!! $footerLogoMarkup !!}</span>
                    @else
                        <a href="{{ esc_url(home_url('/')) }}" class="inline-block mb-4">{!! $footerLogoMarkup !!}</a>
                    @endif
                @endif
                @if($showCompany && $company)
                    <h2 class="text-overline text-content-secondary mb-4">{{ $company }}</h2>
                @endif
                @if($footerText)
                    <div class="footer-prose text-content-secondary text-sm prose prose-sm">
                        {!! wp_kses_post($footerText) !!}
                    </div>
                @endif
            </div>

            {{-- Footer Navigation --}}
            @if($showNav)
                <div>
                    <h2 class="text-overline text-content-secondary mb-4">{{ $navTitle }}</h2>
                    <nav class="footer-nav" aria-label="{{ __('Fußnavigation', 'wp-starter') }}">
                        <?php
                        wp_nav_menu([
                            'container' => false,
                            'menu_class' => 'space-y-2 text-sm',
                            'theme_location' => $navMenu,
                            'fallback_cb' => false,
                            'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                        ]);
                        ?>
                    </nav>
                </div>
            @endif

            {{-- Contact Info --}}
            @if($showContact && ($address || $phone || $fax || $email))
                <div>
                    <h2 class="text-overline text-content-secondary mb-4">{{ $contactTitle }}</h2>
                    <address class="not-italic text-content-secondary text-sm space-y-2">
                        @if($address)
                            <p>{!! nl2br(esc_html($address)) !!}</p>
                        @endif
                        @if($phone)
                            <p>
                                <a href="tel:{{ \WordpressStarter\Helpers\Text::telHref($phone) }}" class="hover:text-content transition-colors">
                                    {{ $phone }}
                                </a>
                            </p>
                        @endif
                        @if($fax)
                            <p>{{ __('Telefax', 'wp-starter') }}: {{ $fax }}</p>
                        @endif
                        @if($email)
                            <p>
                                <a href="{{ esc_url('mailto:' . $email) }}" class="hover:text-content transition-colors">
                                    {{ $email }}
                                </a>
                            </p>
                        @endif
                    </address>
                </div>
            @endif

            {{-- Social Links --}}
            @if($showSocial && !empty($socialLinks))
                <div>
                    <h2 class="text-overline text-content-secondary mb-4">{{ $socialTitle }}</h2>
                    <div class="flex gap-4">
                        @foreach($socialLinks as $social)
                            @if(!empty($social['url']))
                                <a href="{{ esc_url($social['url']) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   {{-- 44px Klickflaeche wie bei den Team-Icons. Das sichtbare Symbol bleibt
                                        24px, nur der anklickbare Bereich waechst; der negative Rand
                                        haelt den optischen Abstand. --}}
                                   class="inline-flex items-center justify-center w-11 h-11 -m-2 text-content-secondary hover:text-content transition-colors rounded-full focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                                   aria-label="{{ ($social['platform'] ?? 'Social Media') . ' ' . __('(öffnet in neuem Tab)', 'wp-starter') }}">
                                    @switch($social['platform'] ?? '')
                                        @case('facebook')
                                            <x-icon name="facebook" class="w-6 h-6" />
                                            @break
                                        @case('instagram')
                                            <x-icon name="instagram" class="w-6 h-6" />
                                            @break
                                        @case('linkedin')
                                            <x-icon name="linkedin" class="w-6 h-6" />
                                            @break
                                        @case('twitter')
                                        @case('x')
                                            <x-icon name="x" class="w-6 h-6" />
                                            @break
                                        @case('youtube')
                                            <x-icon name="youtube" class="w-6 h-6" />
                                            @break
                                        @default
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                                            </svg>
                                    @endswitch
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @endif

    {{-- Bottom Bar --}}
    <div @if(!$reduceFooter) class="border-t border-line" @endif>
        <div class="max-w-[var(--container-max)] mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                {{-- Copyright --}}
                <p class="text-content-secondary text-sm">
                    {{ $copyrightText }}
                </p>

                {{-- Legal Menu --}}
                @if($showLegal)
                    <nav class="legal-nav" aria-label="{{ __('Rechtliche Links', 'wp-starter') }}">
                        <?php
                        wp_nav_menu([
                            'container' => false,
                            'menu_class' => 'flex gap-6 text-sm',
                            'theme_location' => 'legal-menu',
                            'fallback_cb' => false,
                            'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                        ]);
                        ?>
                    </nav>
                @endif
            </div>
        </div>
    </div>
</footer>
@endif
