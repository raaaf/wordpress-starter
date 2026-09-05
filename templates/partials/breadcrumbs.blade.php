@php
    // Only show breadcrumbs if:
    // 1. We're not on the front page
    // 2. We're not on a single post (clean reading experience)
    $showBreadcrumbs = !is_front_page() && !is_singular('post') && !\WordpressStarter\Acf\PageSettings::isLandingPage();
    $hasYoast = function_exists('yoast_breadcrumb');

    $isMemberArea = is_page() && function_exists('get_field') && get_field('page_is_member_area');
    $isAuthenticated = $isMemberArea && \WordpressStarter\MemberArea\Auth::isAuthenticated();

    // Ancestor pages, same order as SeoServiceProvider::getBreadcrumbItems(), so
    // the visual trail below matches the BreadcrumbList JSON-LD that provider
    // already emits on wp_head. Only that one schema exists now; this partial
    // used to render its own second, non-ancestor-aware one.
    $breadcrumbAncestors = [];
    if ($showBreadcrumbs && !$hasYoast && is_page()) {
        $currentPost = get_queried_object();
        if ($currentPost instanceof \WP_Post && $currentPost->post_parent) {
            foreach (array_reverse(get_post_ancestors($currentPost->ID)) as $ancestorId) {
                $breadcrumbAncestors[] = [
                    'title' => get_the_title($ancestorId),
                    'url' => get_permalink($ancestorId),
                ];
            }
        }
    }
@endphp

@if($showBreadcrumbs)
    <div class="bg-surface border-b border-line">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            <nav class="breadcrumbs" aria-label="{{ __('Breadcrumb', 'wp-starter') }}">
                @if($hasYoast)
                    <?php yoast_breadcrumb(); ?>
                @else
                    <ol class="flex items-center gap-1 text-sm text-content-secondary">
                        <li>
                            <a href="{{ esc_url(home_url('/')) }}" class="hover:text-content transition-colors">{{ __('Startseite', 'wp-starter') }}</a>
                        </li>
                        @foreach($breadcrumbAncestors as $ancestor)
                            <li aria-hidden="true" class="text-content-tertiary">»</li>
                            <li>
                                <a href="{{ esc_url($ancestor['url']) }}" class="hover:text-content transition-colors">{{ $ancestor['title'] }}</a>
                            </li>
                        @endforeach
                        @if(!is_front_page())
                            <li aria-hidden="true" class="text-content-tertiary">»</li>
                            <li>
                                <span class="text-content" aria-current="page">{{ get_the_title() }}</span>
                            </li>
                        @endif
                    </ol>
                @endif
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
