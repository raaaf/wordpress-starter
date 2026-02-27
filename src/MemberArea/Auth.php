<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

class Auth
{
    private const COOKIE_NAME = 'wp_member_area_auth';

    public static function getAuthMode(): string
    {
        if (!function_exists('get_field')) {
            return 'password';
        }
        return get_field('member_auth_mode', 'option') ?: 'password';
    }

    public static function isAuthenticated(): bool
    {
        $mode = self::getAuthMode();

        if ($mode === 'wordpress') {
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
        $cookie = sanitize_text_field( wp_unslash( $_COOKIE[self::COOKIE_NAME] ?? '' ) );
        if (empty($cookie)) {
            return false;
        }

        $parts = explode('|', $cookie, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$timestamp, $hmac] = $parts;
        $ttlHours = (int) ( get_field('member_cookie_ttl', 'option') ?: 14 );
        $ttlSeconds = $ttlHours * 3600;

        if ( (int) $timestamp + $ttlSeconds < time()) {
            self::clearCookie();
            return false;
        }

        $passwordHash = get_field('member_shared_password', 'option') ?: '';
        $expectedHmac = hash_hmac('sha256', $timestamp . '|' . $passwordHash, wp_salt('auth'));

        return hash_equals($expectedHmac, $hmac);
    }

    /**
     * @return bool|\WP_Error
     */
    public static function login(string $credential, ?string $password = null): bool|\WP_Error
    {
        $mode = self::getAuthMode();

        if ($mode === 'wordpress') {
            $result = wp_signon([
                'user_login' => $credential,
                'user_password' => $password ?? '',
                'remember' => false,
            ]);

            if (is_wp_error($result)) {
                return $result;
            }

            // wp_signon() sets the cookie but is_user_logged_in() still returns false
            // in the same request — set the current user manually so isAuthenticated() works.
            wp_set_current_user($result->ID);

            return self::isAuthenticated();
        }

        // Password mode
        $passwordHash = get_field('member_shared_password', 'option') ?: '';
        if (empty($passwordHash)) {
            return new \WP_Error('no_password', __('Kein Passwort konfiguriert.', 'wp-starter'));
        }

        if (!wp_check_password($credential, $passwordHash)) {
            return new \WP_Error('wrong_password', __('Falsches Passwort.', 'wp-starter'));
        }

        self::setCookie($passwordHash);
        return true;
    }

    public static function logout(): void
    {
        $mode = self::getAuthMode();

        if ($mode === 'wordpress') {
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

        $ttlHours = (int) ( get_field('member_cookie_ttl', 'option') ?: 14 );
        $expire = time() + ( $ttlHours * 3600 );

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
