@php
    // Only show breadcrumbs if:
    // 1. Yoast SEO is active and breadcrumbs are enabled
    // 2. We're not on the front page
    // 3. We're not on a single post (clean reading experience)
    $showBreadcrumbs = function_exists('yoast_breadcrumb') && !is_front_page() && !is_singular('post');

    $isMemberArea = is_page() && function_exists('get_field') && get_field('page_is_member_area');
    $isAuthenticated = $isMemberArea && \WordpressStarter\MemberArea\Auth::isAuthenticated();
@endphp

@if($showBreadcrumbs)
    <div class="bg-surface border-b border-line">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            <nav class="breadcrumbs" aria-label="{{ __('Breadcrumb', 'wp-starter') }}">
                <?php yoast_breadcrumb(); ?>
            </nav>
            @if($isAuthenticated)
                <x-button
                    url="{{ wp_nonce_url(home_url('/?member_logout=1'), 'member_area_logout') }}"
                    :title="__('Abmelden', 'wp-starter')"
                    variant="secondary"
                    size="sm"
                    class="shrink-0"
                />
            @endif
        </div>
    </div>
@endif
