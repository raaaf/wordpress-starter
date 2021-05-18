<div class="flex items-center justify-between py-6">
    <div class="flex items-center justify-between">
        <div>
            @if ( has_custom_logo() )
            <a href="{{ get_bloginfo( 'url' ) }} title=" {{ get_bloginfo( 'name' ) }}">
                {{ the_custom_logo() }}
            </a>
            @else
            <a href="{{ get_bloginfo( 'url' ) }}" title="{{ get_bloginfo( 'name' ) }}" class="has-h2-font-size">
                {{ get_bloginfo( 'name' ) }}
            </a>
            <p class="has-button-font-size has-secondary-text-color">
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
    'li_class' => 'has-button-font-size',
    'fallback_cb' => false,
    )
    ))
</div>
