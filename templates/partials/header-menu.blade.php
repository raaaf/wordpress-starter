<div class="flex items-center justify-between py-6">
    <div class="flex items-center justify-between">
        <div>
            @php($custom_logo_id = get_theme_mod('custom_logo'))
            @php($logo = $custom_logo_id ? wp_get_attachment_image_src($custom_logo_id, 'full') : null)

            <a href="{{ esc_url(get_bloginfo('url')) }}" title="{{ esc_attr(get_bloginfo('name')) }}"
                class="inline-block transition-opacity duration-300 hover:opacity-75">
                <img src="{{ $logo ? esc_url($logo[0]) : esc_url(get_template_directory_uri() . '/resources/img/default-logo.png') }}"
                    alt="{{ esc_attr(get_bloginfo('name')) }}" loading="lazy" width="50" height="50" class="w-full max-w-[50px]">
            </a>
        </div>
    </div>

    @php(
    wp_nav_menu([
        'container_id' => 'header-menu',
        'container_class' => 'site-header-menu',
        'menu_class' => 'flex',
        'theme_location' => 'header-menu',
        'li_class' => 'has-button',
        'fallback_cb' => false,
        'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar" aria-label="Main Navigation">%3$s</ul>',
    ])
)
</div>
