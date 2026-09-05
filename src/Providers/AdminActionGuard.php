<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Shared guard for admin_init handlers gated by a $_GET flag.
 *
 * Every such handler repeats the same shape: bail out if the query param is
 * absent, verify its nonce, then check a capability, wp_die()ing on either
 * failure. Only the nonce action, capability, and capability-failure message
 * differ per handler; the nonce-failure message is identical everywhere it
 * is used, so it stays fixed inside the helper.
 */
trait AdminActionGuard
{
    /**
     * @return bool True if $param is present and the nonce/capability checks
     *              passed, so the caller should proceed. False if $param is
     *              absent, so the caller should return early. Never returns
     *              false for a failed nonce or capability check: those wp_die().
     */
    private static function verifyAdminAction(
        string $param,
        string $nonceAction,
        string $capability,
        string $capabilityMessage
    ): bool {
        if (!isset($_GET[$param])) {
            return false;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, $nonceAction)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can($capability)) {
            wp_die(esc_html($capabilityMessage));
        }

        return true;
    }
}
