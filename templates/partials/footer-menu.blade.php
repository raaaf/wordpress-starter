<footer class="flex items-center justify-between py-6">
    <div>
        @php(
    wp_nav_menu([
        'container_id' => 'footer-menu',
        'container_class' => 'site-footer-menu',
        'menu_class' => 'flex',
        'theme_location' => 'footer-menu',
        'li_class' => 'has-button',
        'fallback_cb' => false,
        'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar" aria-label="Footer Navigation">%3$s</ul>',
    ])
)
    </div>

    <div>
        @php(
    wp_nav_menu([
        'container_id' => 'legal-menu',
        'container_class' => 'site-footer-menu',
        'menu_class' => 'flex',
        'theme_location' => 'legal-menu',
        'li_class' => 'has-button',
        'fallback_cb' => false,
        'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar" aria-label="Legal Stuff">%3$s</ul>',
    ])
)
    </div>
</footer>
