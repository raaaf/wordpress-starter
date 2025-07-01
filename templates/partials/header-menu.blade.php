<div x-data="navigation" class="relative">
    <div class="flex items-center justify-between py-6">
        <div>
            @php($custom_logo_id = get_theme_mod('custom_logo'))
            @php($logo = $custom_logo_id ? wp_get_attachment_image_src($custom_logo_id, 'full') : null)

            <a href="{{ esc_url(get_bloginfo('url')) }}" title="{{ esc_attr(get_bloginfo('name')) }}"
                class="inline-block transition-opacity duration-300 hover:opacity-75">
                <img src="{{ $logo ? esc_url($logo[0]) : esc_url(get_template_directory_uri() . '/resources/img/default-logo.png') }}"
                    alt="{{ esc_attr(get_bloginfo('name')) }}" loading="lazy" width="50" height="50" class="w-full max-w-[50px]">
            </a>
        </div>

        {{-- Mobile menu button --}}
        <button @click="toggle()" 
                class="lg:hidden p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary"
                aria-label="Toggle navigation menu"
                :aria-expanded="isOpen">
            <svg x-show="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg x-show="isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        {{-- Desktop navigation --}}
        <nav class="hidden lg:block">
            @php(
            wp_nav_menu([
                'container' => false,
                'menu_class' => 'flex space-x-8',
                'theme_location' => 'header-menu',
                'li_class' => 'relative',
                'fallback_cb' => false,
                'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar" aria-label="Main Navigation">%3$s</ul>',
            ])
        )
        </nav>
    </div>

    {{-- Mobile navigation --}}
    <nav x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         @click.away="close()"
         class="absolute top-full left-0 right-0 bg-white shadow-lg rounded-md lg:hidden"
         style="display: none;">
        @php(
        wp_nav_menu([
            'container' => false,
            'menu_class' => 'py-2',
            'theme_location' => 'header-menu',
            'li_class' => 'px-4 py-2 hover:bg-gray-50',
            'fallback_cb' => false,
            'items_wrap' => '<ul id="%1$s" class="%2$s" role="menu" aria-label="Mobile Navigation">%3$s</ul>',
        ])
    )
    </nav>
</div>
