<div class="flex items-center justify-between">

    <div>
        @php(
        wp_nav_menu(
        array(
        'container_id' => 'footer-menu',
        'container_class' => '',
        'menu_class' => 'flex',
        'theme_location' => 'footer-menu',
        'li_class' => 'has-button',
        'fallback_cb' => false,
        )
        ))
    </div>

    <div>
        @php(
        wp_nav_menu(
        array(
        'container_id' => 'legal-menu',
        'container_class' => '',
        'menu_class' => 'flex',
        'theme_location' => 'legal-menu',
        'li_class' => 'has-button',
        'fallback_cb' => false,
        )
        ))
    </div>

</div>
