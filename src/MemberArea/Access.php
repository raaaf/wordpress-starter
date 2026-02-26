<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

class Access
{
    public static function register(): void
    {
        add_filter('template_include', [self::class, 'checkAccess'], 11);
    }

    public static function checkAccess(string $template): string
    {
        // Check backend toggle (ACF is loaded at this point)
        if (function_exists('get_field')) {
            $active = get_field('member_area_active', 'option');
            if ($active !== null && !$active) {
                return $template;
            }
        }

        if (!is_page()) {
            return $template;
        }

        // Member area dashboard page
        $isMemberArea = get_field('page_is_member_area');
        if ($isMemberArea) {
            $blade = $GLOBALS['blade'] ?? null;
            if (!$blade) {
                return $template;
            }

            if (!Auth::isAuthenticated()) {
                $GLOBALS['template_name'] = 'member-area.login-page';
            } else {
                $GLOBALS['template_name'] = 'page-member-area';
            }

            return get_template_directory() . '/config/index.php';
        }

        // Protected page — redirect to login if not authenticated
        $isProtected = get_field('page_is_protected');
        if (!$isProtected) {
            return $template;
        }

        if (Auth::isAuthenticated()) {
            return $template;
        }

        $blade = $GLOBALS['blade'] ?? null;
        if (!$blade) {
            return $template;
        }

        $GLOBALS['template_name'] = 'member-area.login-page';
        return get_template_directory() . '/config/index.php';
    }
}
