@php
    // Only show breadcrumbs if:
    // 1. Yoast SEO is active and breadcrumbs are enabled
    // 2. We're not on the front page
    // 3. We're not on a single post (clean reading experience)
    $showBreadcrumbs = function_exists('yoast_breadcrumb') && !is_front_page() && !is_singular('post');
@endphp

@if($showBreadcrumbs)
    <div class="bg-surface-secondary border-b border-line">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <nav class="breadcrumbs" aria-label="{{ __('Breadcrumb', 'wp-starter') }}">
                <?php yoast_breadcrumb(); ?>
            </nav>
        </div>
    </div>
@endif
