<div class="flex items-center justify-between py-6">
    <div class="flex items-center justify-between">
        <div>
            @if ( has_custom_logo() )
            @php( $custom_logo_id = get_theme_mod( 'custom_logo' ) )
            @php( $logo = wp_get_attachment_image_src( $custom_logo_id , 'full' ) )
                <a href="{{ get_bloginfo( 'url' ) }}" title="{{ get_bloginfo( 'name' ) }}" class="inline-block transition-opacity duration-300 hover:opacity-75">
                    <img src="{{ esc_url( $logo[0] ) }}" alt="{{ get_bloginfo( 'name' ) }}">
                </a>
            @else
            <a href="{{ get_bloginfo( 'url' ) }}" title="{{ get_bloginfo( 'name' ) }}" class="typo-h2">
                {{ get_bloginfo( 'name' ) }}
            </a>
            <p class="typo-button typo-secondary-color">
                {{ get_bloginfo( 'description' ) }}
            </p>
            @endif
        </div>

    </div>

    @php(
    wp_nav_menu(
    array(
    'container_id' => 'header-menu',
    'container_class' => '',
    'menu_class' => 'flex',
    'theme_location' => 'header-menu',
    'li_class' => 'typo-button',
    'fallback_cb' => false,
    )
    ))
</div>
