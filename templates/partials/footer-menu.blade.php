@php
    // Get footer options - Info column
    $showLogo = \WordpressStarter\Acf\Fields::option('footer_show_logo', true);
    $showCompany = \WordpressStarter\Acf\Fields::option('footer_show_company', true);
    $footerText = \WordpressStarter\Acf\Fields::option('footer_text', '');

    // Navigation column
    $showNav = \WordpressStarter\Acf\Fields::option('footer_show_nav', true);
    $navTitle = \WordpressStarter\Acf\Fields::option('footer_nav_title') ?: __('Navigation', 'wp-starter');
    $navMenu = \WordpressStarter\Acf\Fields::option('footer_nav_menu', 'footer-menu');

    // Contact column
    $showContact = \WordpressStarter\Acf\Fields::option('footer_show_contact', true);
    $contactTitle = \WordpressStarter\Acf\Fields::option('footer_contact_title') ?: __('Kontakt', 'wp-starter');

    // Social column
    $showSocial = \WordpressStarter\Acf\Fields::option('footer_show_social', true);
    $socialTitle = \WordpressStarter\Acf\Fields::option('footer_social_title') ?: __('Folge uns', 'wp-starter');

    // Bottom bar
    $defaultCopyright = __('© {year} Firmenname. Alle Rechte vorbehalten.', 'wp-starter');
    $copyrightText = \WordpressStarter\Acf\Fields::option('copyright_text') ?: $defaultCopyright;
    $showLegal = \WordpressStarter\Acf\Fields::option('footer_show_legal', true);

    // Get contact info from general settings
    $company = \WordpressStarter\Acf\Fields::option('company_name', '');
    $address = \WordpressStarter\Acf\Fields::option('address', '');
    $phone = \WordpressStarter\Acf\Fields::option('phone', '');
    $email = \WordpressStarter\Acf\Fields::option('email', '');

    // Get social links
    $socialLinks = \WordpressStarter\Acf\Fields::option('social_links', []);

    // Get logo (same logic as header)
    $logo_url = null;
    if ($showLogo) {
        $acf_logo = \WordpressStarter\Acf\Fields::option('site_logo');
        if ($acf_logo && !empty($acf_logo['url'])) {
            $logo_url = $acf_logo['url'];
        } else {
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $logo_data = wp_get_attachment_image_src($custom_logo_id, 'logo');
                if ($logo_data) {
                    $logo_url = $logo_data[0];
                }
            }
        }
    }

    // Replace {year} placeholder
    $copyrightText = str_replace('{year}', wp_date('Y'), $copyrightText);
@endphp

<footer class="bg-surface border-t border-line">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            {{-- Logo / Company Info / Footer Text --}}
            <div class="lg:col-span-1">
                @if($showLogo && $logo_url)
                    <a href="{{ esc_url(home_url('/')) }}" class="inline-block mb-4">
                        <img src="{{ esc_url($logo_url) }}"
                             alt="{{ esc_attr(get_bloginfo('name')) }}"
                             class="h-10 w-auto">
                    </a>
                @endif
                @if($showCompany && $company)
                    <h3 class="text-h5 mb-4">{{ $company }}</h3>
                @endif
                @if($footerText)
                    <div class="text-content-secondary text-sm prose prose-sm">
                        {!! wp_kses_post($footerText) !!}
                    </div>
                @endif
            </div>

            {{-- Footer Navigation --}}
            @if($showNav)
                <div>
                    <h3 class="text-h5 mb-4">{{ $navTitle }}</h3>
                    <nav class="footer-nav" aria-label="{{ __('Fußnavigation', 'wp-starter') }}">
                        <?php
                        wp_nav_menu([
                            'container' => false,
                            'menu_class' => 'space-y-2',
                            'theme_location' => $navMenu,
                            'fallback_cb' => false,
                            'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                        ]);
                        ?>
                    </nav>
                </div>
            @endif

            {{-- Contact Info --}}
            @if($showContact && ($address || $phone || $email))
                <div>
                    <h3 class="text-h5 mb-4">{{ $contactTitle }}</h3>
                    <address class="not-italic text-content-secondary text-sm space-y-2">
                        @if($address)
                            <p>{!! nl2br(esc_html($address)) !!}</p>
                        @endif
                        @if($phone)
                            <p>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-content transition-colors">
                                    {{ $phone }}
                                </a>
                            </p>
                        @endif
                        @if($email)
                            <p>
                                <a href="mailto:{{ $email }}" class="hover:text-content transition-colors">
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
                    <h3 class="text-h5 mb-4">{{ $socialTitle }}</h3>
                    <div class="flex gap-4">
                        @foreach($socialLinks as $social)
                            @if(!empty($social['url']))
                                <a href="{{ esc_url($social['url']) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-content-secondary hover:text-content transition-colors"
                                   aria-label="{{ ($social['platform'] ?? 'Social Media') . ' ' . __('(öffnet in neuem Tab)', 'wp-starter') }}">
                                    @switch($social['platform'] ?? '')
                                        @case('facebook')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                                            </svg>
                                            @break
                                        @case('instagram')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/>
                                            </svg>
                                            @break
                                        @case('linkedin')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                            </svg>
                                            @break
                                        @case('twitter')
                                        @case('x')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                            @break
                                        @case('youtube')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                            </svg>
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

    {{-- Bottom Bar --}}
    <div class="border-t border-line">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
