<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use WP_Error;

class Auth
{
    private const COOKIE_NAME = 'wp_member_area_auth';
    private const MODE_WORDPRESS = 'wordpress'; // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled

    private static string|false $cachedAuthMode = false;

    private static int|false $cachedCookieTtl = false;

    private static string|false $cachedSharedPassword = false;

    public static function getAuthMode(): string
    {
        if (!function_exists('get_field')) {
            return 'password';
        }
        if (self::$cachedAuthMode === false) {
            self::$cachedAuthMode = get_field('member_auth_mode', 'option') ?: 'password';
        }

        return self::$cachedAuthMode;
    }

    private static function isWordpressMode(): bool
    {
        return strtolower(self::getAuthMode()) === self::MODE_WORDPRESS;
    }

    private static function getCookieTtl(): int
    {
        if (self::$cachedCookieTtl === false) {
            self::$cachedCookieTtl = (int) ( get_field('member_cookie_ttl', 'option') ?: 14 );
        }

        return self::$cachedCookieTtl;
    }

    private static function getSharedPassword(): string
    {
        if (self::$cachedSharedPassword === false) {
            self::$cachedSharedPassword = get_field('member_shared_password', 'option') ?: '';
        }

        return self::$cachedSharedPassword;
    }

    /**
     * Whether the member area / page protection feature is active at all
     * (the "Interner Bereich aktiv" backend toggle). Mirrors
     * Access::isProtectionActive() (same field), duplicated here so Auth,
     * DownloadQuery and FileHandler can gate their AJAX handlers without a
     * dependency on Access.
     */
    public static function isMemberAreaActive(): bool
    {
        if (!function_exists('get_field')) {
            return true;
        }

        $active = get_field('member_area_active', 'option');

        return $active === null || (bool) $active;
    }

    public static function isAuthenticated(): bool
    {
        // Administrators always have access regardless of auth mode
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return true;
        }

        if (self::isWordpressMode()) {
            if (!is_user_logged_in()) {
                return false;
            }
            $allowedRoles = get_field('member_allowed_roles', 'option') ?: [];
            if (empty($allowedRoles)) {
                return true;
            }
            $user = wp_get_current_user();

            return !empty(array_intersect($user->roles, (array) $allowedRoles));
        }

        // Password mode: validate HMAC-signed cookie
        $cookie = sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME] ?? ''));
        if (empty($cookie)) {
            return false;
        }

        $parts = explode('|', $cookie, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$timestamp, $hmac] = $parts;
        $ttlSeconds = self::getCookieTtl() * 3600;

        if ( (int) $timestamp + $ttlSeconds < time()) {
            self::clearCookie();

            return false;
        }

        $passwordHash = self::getSharedPassword();
        $expectedHmac = hash_hmac('sha256', $timestamp . '|' . $passwordHash, wp_salt('auth'));

        return hash_equals($expectedHmac, $hmac);
    }

    /**
     * @return bool|WP_Error
     */
    public static function login(string $credential, ?string $password = null): bool|WP_Error
    {
        if (!self::isMemberAreaActive()) {
            return new WP_Error('member_area_inactive', __('Interner Bereich ist deaktiviert.', 'wp-starter'));
        }

        // Defense-in-depth: throttle callers that bypass the AJAX wrapper.
        // Uses a separate key ('member_login_auth') with the same budget as the
        // wrapper's 'member_login' key (5 attempts / 300 s). Both counters
        // increment on every call, so when the wrapper already enforced its limit
        // this inner check never trips first — the inner counter reaches 5 only
        // after the outer one does. Direct callers (e.g. CLI, tests) are still
        // capped at the same budget.
        \WordpressStarter\RateLimiter::enforce('member_login_auth', 5, 300);

        if (self::isWordpressMode()) {
            $result = wp_signon([
                'user_login' => $credential,
                'user_password' => $password ?? '',
                'remember' => false,
            ], is_ssl());

            if (is_wp_error($result)) {
                \WordpressStarter\Providers\LogServiceProvider::info('Member login failed', [
                    'wp_error_code' => $result->get_error_code(),
                ]);

                // Only collapse the enumeration-relevant codes into a generic message.
                // Other WP_Error codes (e.g. account blocked by a security plugin, no
                // such user on a closed-registration site) are structural failures, not
                // retryable credential mistakes, so their message is passed through.
                $enumerationCodes = ['invalid_username', 'incorrect_password', 'invalid_email', 'invalidcombo'];
                if (in_array($result->get_error_code(), $enumerationCodes, true)) {
                    return new WP_Error('member_login_failed', __('Falsches Passwort.', 'wp-starter'));
                }

                return $result;
            }

            // wp_signon() sets the cookie but is_user_logged_in() still returns false
            // in the same request — set the current user manually so isAuthenticated() works.
            wp_set_current_user($result->ID);

            if (!self::isAuthenticated()) {
                // The credential was correct but the account's role is not in
                // member_allowed_roles. wp_signon() already set the real WordPress
                // auth cookies, so without this the visitor stays regularly logged
                // into wp-admin even though the member-area check just denied them.
                wp_clear_auth_cookie();
                wp_set_current_user(0);

                return new WP_Error('member_login_role_not_allowed', __('Kein Zugriff auf den internen Bereich.', 'wp-starter'));
            }

            return true;
        }

        // Password mode
        $passwordHash = self::getSharedPassword();
        if (empty($passwordHash)) {
            return new WP_Error('no_password', __('Kein Passwort konfiguriert.', 'wp-starter'));
        }

        if (!wp_check_password($credential, $passwordHash)) {
            return new WP_Error('wrong_password', __('Falsches Passwort.', 'wp-starter'));
        }

        self::setCookie($passwordHash);

        return true;
    }

    public static function logout(): void
    {
        if (self::isWordpressMode()) {
            wp_logout();

            return;
        }

        self::clearCookie();
    }

    private static function setCookie(string $passwordHash): void
    {
        $timestamp = (string) time();
        $hmac = hash_hmac('sha256', $timestamp . '|' . $passwordHash, wp_salt('auth'));
        $value = $timestamp . '|' . $hmac;

        $expire = time() + ( self::getCookieTtl() * 3600 );

        $secure = is_ssl();

        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expire,
            'path' => COOKIEPATH ?: '/',
            'domain' => COOKIE_DOMAIN ?: '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Also set in current request
        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => COOKIEPATH ?: '/',
            'domain' => COOKIE_DOMAIN ?: '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE_NAME]);
    }
}
