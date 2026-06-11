@php
    // Only show breadcrumbs if:
    // 1. We're not on the front page
    // 2. We're not on a single post (clean reading experience)
    $showBreadcrumbs = !is_front_page() && !is_singular('post');
    $hasYoast = function_exists('yoast_breadcrumb');

    $isMemberArea = is_page() && function_exists('get_field') && get_field('page_is_member_area');
    $isAuthenticated = $isMemberArea && \WordpressStarter\MemberArea\Auth::isAuthenticated();
@endphp

@if($showBreadcrumbs)
    <div class="bg-surface border-b border-line">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            <nav class="breadcrumbs" aria-label="{{ __('Breadcrumb', 'wp-starter') }}">
                @if($hasYoast)
                    <?php yoast_breadcrumb(); ?>
                @else
                    @php
                        $breadcrumbItems = [
                            [
                                '@type'    => 'ListItem',
                                'position' => 1,
                                'name'     => __('Startseite', 'wp-starter'),
                                'item'     => home_url('/'),
                            ],
                        ];
                        if (!is_front_page()) {
                            $breadcrumbItems[] = [
                                '@type'    => 'ListItem',
                                'position' => 2,
                                'name'     => get_the_title(),
                                'item'     => get_permalink() ?: home_url('/'),
                            ];
                        }
                        $breadcrumbSchema = [
                            '@context'        => 'https://schema.org',
                            '@type'           => 'BreadcrumbList',
                            'itemListElement' => $breadcrumbItems,
                        ];
                        $nonce = $GLOBALS['csp_nonce'] ?? '';
                    @endphp
                    <script type="application/ld+json" @if($nonce) nonce="{{ $nonce }}" @endif>
                        {!! wp_json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
                    </script>
                    <ol class="flex items-center gap-1 text-sm text-content-secondary">
                        <li>
                            <a href="{{ home_url('/') }}" class="hover:text-content transition-colors">{{ __('Startseite', 'wp-starter') }}</a>
                        </li>
                        @if(!is_front_page())
                            <li aria-hidden="true" class="text-content-tertiary">›</li>
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
