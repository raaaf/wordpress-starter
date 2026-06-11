<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

use WordpressStarter\ThemeContext;

/**
 * Configures Contact Form 7 plugin
 *
 * Settings applied:
 * - Auto-P in forms: Disabled (cleaner HTML)
 *
 * Note: CF7 has limited global settings. Most configuration is per-form.
 * This configurator primarily sets up sensible defaults.
 *
 * @see https://wordpress.org/plugins/contact-form-7/
 */
class ContactForm7Configurator extends AbstractPluginConfigurator
{
    /** Hidden honeypot field name injected into every CF7 form. */
    private const HONEYPOT_FIELD = 'your-website';

    /** Hidden field carrying the signed render timestamp. */
    private const TIMESTAMP_FIELD = '_cf7_rendered_at';

    /** Submissions faster than this (seconds) are treated as bots. */
    private const MIN_SUBMIT_SECONDS = 3;

    /** Maximum number of URLs allowed across all submitted fields. */
    private const MAX_URLS = 2;

    public static function getPluginSlug(): string
    {
        return 'contact-form-7';
    }

    public static function isPluginActive(): bool
    {
        return defined('WPCF7_VERSION');
    }

    protected static function doConfigure(): void
    {
        // CF7 has very limited global options
        // Most settings are stored per-form as post meta

        // Set global settings
        $settings = [
            // Don't load CF7 assets everywhere (theme handles conditional loading)
            'load_js' => 0,
            'load_css' => 0,
        ];

        // CF7 stores settings via WPCF7 class
        if (class_exists('WPCF7')) {
            foreach ($settings as $key => $value) {
                \WPCF7::update_option($key, $value);
            }
        }

        self::markConfigured();
    }

    /**
     * Register filters that need to run on every page load
     *
     * Called from PluginConfiguratorServiceProvider::boot()
     * These filters run regardless of configuration state.
     */
    public static function registerFilters(): void
    {
        if (!self::isPluginActive()) {
            return;
        }

        // Disable auto-p in forms (produces cleaner HTML)
        add_filter('wpcf7_autop_or_not', '__return_false');

        // Spam protection: inject honeypot + signed timestamp into every form.
        add_filter('wpcf7_form_elements', [self::class, 'injectSpamTraps']);

        // Spam protection: server-side heuristics (honeypot, time-trap, links, keywords).
        add_filter('wpcf7_spam', [self::class, 'detectSpam'], 10, 2);

        // Use custom validation messages in German
        add_filter('wpcf7_default_validation_error_message', function (): string {
            return __('Bitte korrigieren Sie die markierten Felder.', 'wp-starter');
        });
    }

    /**
     * Inject a hidden honeypot field and a signed render timestamp into the form.
     *
     * Real users never see or fill the honeypot. The timestamp lets us reject
     * submissions that arrive implausibly fast (bots). Both are validated in
     * {@see self::detectSpam()}.
     */
    public static function injectSpamTraps(string $elements): string
    {
        $timestamp = time();
        $token = $timestamp . '|' . self::signTimestamp($timestamp);

        $traps = sprintf(
            '<div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;overflow:hidden;">'
                . '<label>Bitte dieses Feld leer lassen</label>'
                . '<input type="text" name="%1$s" value="" tabindex="-1" autocomplete="off">'
                . '</div>'
                . '<input type="hidden" name="%2$s" value="%3$s">',
            esc_attr(self::HONEYPOT_FIELD),
            esc_attr(self::TIMESTAMP_FIELD),
            esc_attr($token),
        );

        return $elements . $traps;
    }

    /**
     * Server-side spam heuristics applied to every CF7 submission.
     *
     * @param mixed $spam Whether a prior filter already flagged this submission.
     * @param mixed $submission The WPCF7_Submission instance.
     */
    public static function detectSpam(mixed $spam, mixed $submission = null): bool
    {
        if ($spam) {
            return true;
        }

        // 1. Honeypot: only bots fill the hidden field.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $honeypot = isset($_POST[self::HONEYPOT_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::HONEYPOT_FIELD])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        if ($honeypot !== '') {
            return self::flag($submission, 'honeypot', 'Honeypot field was filled');
        }

        // 2. Time-trap: forms submitted faster than a human can type.
        if (self::submittedTooFast()) {
            return self::flag($submission, 'time-trap', 'Form submitted too fast');
        }

        // 3. Content heuristics on all submitted text.
        $text = self::collectText($submission);

        if (self::countUrls($text) > self::MAX_URLS) {
            return self::flag($submission, 'too-many-links', 'Too many URLs in submission');
        }

        if (self::containsDisallowedKeyword($text)) {
            return self::flag($submission, 'keyword', 'Disallowed keyword in submission');
        }

        return false;
    }

    /**
     * Count the number of URLs in a text blob.
     */
    public static function countUrls(string $text): int
    {
        return (int) preg_match_all('#https?://|www\.#i', $text);
    }

    /**
     * Check the text against a conservative, high-confidence spam keyword list.
     *
     * Extend per site via the `<prefix>_cf7_spam_keywords` filter.
     */
    public static function containsDisallowedKeyword(string $text): bool
    {
        $keywords = apply_filters(ThemeContext::prefix() . '_cf7_spam_keywords', self::defaultSpamKeywords());
        $haystack = strtolower($text);

        foreach ( (array) $keywords as $keyword) {
            $keyword = strtolower( (string) $keyword);
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * High-confidence spam terms that never appear in legitimate inquiries.
     *
     * @return array<int, string>
     */
    private static function defaultSpamKeywords(): array
    {
        return [
            'viagra', 'cialis', 'levitra', 'casino', 'porn', 'escort',
            'xxx', 'replica watches', 'rolex replica', 'payday loan',
        ];
    }

    /**
     * Concatenate all posted text values for content analysis.
     */
    private static function collectText(mixed $submission): string
    {
        if (!is_object($submission) || !method_exists($submission, 'get_posted_data')) {
            return '';
        }

        $parts = [];
        foreach ( (array) $submission->get_posted_data() as $value) {
            if (is_array($value)) {
                $value = implode(' ', array_map('strval', $value));
            }
            $parts[] = (string) $value;
        }

        return implode(' ', $parts);
    }

    /**
     * Reject submissions that arrive faster than a human could fill the form.
     *
     * Fails open when the timestamp is missing or its signature does not match
     * (e.g. a page-cached form), so legitimate users are never blocked.
     */
    private static function submittedTooFast(): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw = isset($_POST[self::TIMESTAMP_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::TIMESTAMP_FIELD])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        if ($raw === '' || !str_contains($raw, '|')) {
            return false;
        }

        [$timestamp, $signature] = explode('|', $raw, 2);
        $timestamp = (int) $timestamp;

        if (!hash_equals(self::signTimestamp($timestamp), $signature)) {
            return false;
        }

        return ( time() - $timestamp ) < self::MIN_SUBMIT_SECONDS;
    }

    /**
     * HMAC-sign the render timestamp so it cannot be forged client-side.
     */
    private static function signTimestamp(int $timestamp): string
    {
        $salt = function_exists('wp_salt') ? wp_salt('nonce') : 'cf7-spam-trap';

        return hash_hmac('sha256', (string) $timestamp, $salt);
    }

    /**
     * Record a spam reason on the submission (visible in CF7/Flamingo) and flag it.
     */
    private static function flag(mixed $submission, string $agent, string $reason): bool
    {
        if (is_object($submission) && method_exists($submission, 'add_spam_log')) {
            $submission->add_spam_log([
                'agent' => 'theme-' . $agent,
                'reason' => $reason,
            ]);
        }

        return true;
    }

    public static function getConfigurationSummary(): string
    {
        return __('Contact Form 7: Auto-Formatierung deaktiviert, Assets-Laden optimiert', 'wp-starter');
    }
}
