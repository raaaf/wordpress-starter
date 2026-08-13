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
    /** Option, in der die ID des vom Theme angelegten Formulars steht. */
    private const DEFAULT_FORM_OPTION = 'wp_starter_default_contact_form';

    /** Marker am Formular, damit das Theme sein eigenes wiedererkennt. */
    private const DEFAULT_FORM_META = '_wp_starter_default_form';

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

        // Ein fertiges Formular anlegen, sobald das Plugin aktiv ist.
        add_action('admin_init', [self::class, 'ensureDefaultForm']);

        // Spam protection: inject honeypot + signed timestamp into every form.
        add_filter('wpcf7_form_elements', [self::class, 'injectSpamTraps']);

        // Pflichtfelder sichtbar kennzeichnen.
        add_filter('wpcf7_form_elements', [self::class, 'markRequiredFields']);

        // Spam protection: server-side heuristics (honeypot, time-trap, links, keywords).
        add_filter('wpcf7_spam', [self::class, 'detectSpam'], 10, 2);

        // Use custom validation messages in German
        add_filter('wpcf7_default_validation_error_message', function (): string {
            return __('Bitte korrigier die markierten Felder.', 'wp-starter');
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

    /**
     * Pflichtfelder im Formular sichtbar kennzeichnen.
     *
     * CF7 setzt am Eingabefeld aria-required, sichtbar war das aber nirgends:
     * ein Besucher erfuhr erst nach dem Absenden, welches Feld fehlte.
     *
     * Warum serverseitig und nicht per CSS: CF7 legt das Eingabefeld in das
     * Label. Ein ::after am Label landet dadurch unter dem Feld statt hinter
     * dem Beschriftungstext. Der Marker muss also zwischen Text und Feld, und
     * dorthin kommt nur, wer das Markup anfasst.
     *
     * Das Sternchen traegt aria-hidden, weil aria-required dieselbe Information
     * bereits ansagt.
     *
     * @param string $elements Das gerenderte Formular-HTML.
     */
    public static function markRequiredFields(string $elements): string
    {
        return (string) preg_replace_callback(
            '#<label\b[^>]*>.*?</label>#is',
            static function (array $match): string {
                $label = $match[0];

                if (!str_contains($label, 'wpcf7-validates-as-required')) {
                    return $label;
                }

                if (str_contains($label, 'required-marker')) {
                    return $label;
                }

                $marker = '<span class="required-marker" aria-hidden="true">*</span>';

                // Vor das erste Feld-Wrapper-Element, also direkt hinter den
                // Beschriftungstext.
                $replaced = preg_replace(
                    '#(<span[^>]*class="[^"]*wpcf7-form-control-wrap)#i',
                    $marker . '$1',
                    $label,
                    1
                );

                return is_string($replaced) ? $replaced : $label;
            },
            $elements
        );
    }

    /**
     * ID des vom Theme angelegten Formulars, oder 0.
     */
    public static function defaultFormId(): int
    {
        $id = (int) get_option(self::DEFAULT_FORM_OPTION, 0);

        return ( $id > 0 && get_post_status($id) !== false ) ? $id : 0;
    }

    /**
     * Einmalig ein fertiges Kontaktformular anlegen.
     *
     * Warum das Theme das uebernimmt: das Standardformular von Contact Form 7
     * duzt, kennzeichnet keine Pflichtfelder, hat keine Einwilligung und traegt
     * im Absender die Adresse des Absenders, was bei den meisten Hostern im
     * SPF-Check haengenbleibt. Jede Kundenseite fing damit bei null an.
     *
     * Angelegt wird genau einmal. Der Marker am Beitrag und die Option
     * verhindern Duplikate, und ein manuell geaendertes Formular wird nie
     * ueberschrieben.
     */
    public static function ensureDefaultForm(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite() || !self::isPluginActive()) {
            return;
        }

        if (self::defaultFormId() > 0) {
            return;
        }

        if (!class_exists('WPCF7_ContactForm')) {
            return;
        }

        $existing = get_posts([
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => self::DEFAULT_FORM_META,
        ]);

        if (!empty($existing)) {
            update_option(self::DEFAULT_FORM_OPTION, (int) $existing[0]);

            return;
        }

        $form = \WPCF7_ContactForm::get_template(['title' => __('Kontaktformular', 'wp-starter')]);
        $form->set_properties([
            'form' => self::defaultFormTemplate(),
            'mail' => self::defaultMailTemplate(),
            'messages' => self::defaultMessages(),
        ]);

        $id = $form->save();
        if (!$id) {
            return;
        }

        update_post_meta($id, self::DEFAULT_FORM_META, 1);
        update_option(self::DEFAULT_FORM_OPTION, (int) $id);
    }

    /**
     * Aufbau des Formulars.
     *
     * Gesiezt, weil der Rest des Themes siezt. Pflichtfelder sind Name, E-Mail
     * und Nachricht: ein Betreff laesst sich aus der Nachricht ableiten, eine
     * leere Nachricht nicht. Die Einwilligung ist ein echtes acceptance-Feld,
     * damit CF7 sie serverseitig prueft, statt sie nur anzuzeigen.
     */
    private static function defaultFormTemplate(): string
    {
        $privacyUrl = get_privacy_policy_url();
        $privacyLink = $privacyUrl
            ? sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                esc_url($privacyUrl),
                esc_html__('Datenschutzerklärung', 'wp-starter')
            )
            : esc_html__('Datenschutzerklärung', 'wp-starter');

        return implode("\n", [
            '<label>' . esc_html__('Name', 'wp-starter'),
            '    [text* your-name autocomplete:name]</label>',
            '',
            '<label>' . esc_html__('E-Mail-Adresse', 'wp-starter'),
            '    [email* your-email autocomplete:email]</label>',
            '',
            '<label>' . esc_html__('Betreff', 'wp-starter'),
            '    [text your-subject]</label>',
            '',
            '<label>' . esc_html__('Deine Nachricht', 'wp-starter'),
            '    [textarea* your-message]</label>',
            '',
            '[acceptance privacy-consent]'
                . sprintf(
                    /* translators: %s: link to the privacy policy */
                    esc_html__('Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zu.', 'wp-starter'),
                    $privacyLink
                )
                . '[/acceptance]',
            '',
            '[submit "' . esc_attr__('Nachricht senden', 'wp-starter') . '"]',
        ]);
    }

    /**
     * Mailvorlage.
     *
     * Absender ist die Seite selbst, nicht der Einsender: sonst verschickt der
     * Server Mail im Namen einer fremden Domain und faellt bei SPF und DMARC
     * durch. Die Adresse des Einsenders steht im Reply-To, damit die Antwort
     * trotzdem direkt bei ihm landet.
     *
     * @return array<string, mixed>
     */
    private static function defaultMailTemplate(): array
    {
        $siteName = get_bloginfo('name');
        $siteDomain = wp_parse_url(home_url(), PHP_URL_HOST) ?: 'example.com';
        $siteDomain = preg_replace('/^www\./', '', (string) $siteDomain);

        $body = implode("\n", [
            esc_html__('Neue Nachricht über das Kontaktformular:', 'wp-starter'),
            '',
            esc_html__('Name:', 'wp-starter') . ' [your-name]',
            esc_html__('E-Mail:', 'wp-starter') . ' [your-email]',
            esc_html__('Betreff:', 'wp-starter') . ' [your-subject]',
            '',
            esc_html__('Nachricht:', 'wp-starter'),
            '[your-message]',
            '',
            '--',
            esc_html__('Gesendet am [_date] um [_time] von [_site_title] ([_site_url])', 'wp-starter'),
            esc_html__('Einwilligung Datenschutz:', 'wp-starter') . ' [privacy-consent]',
        ]);

        return [
            'subject' => sprintf('[%s] [your-subject]', $siteName),
            'sender' => sprintf('%s <noreply@%s>', $siteName, $siteDomain),
            'recipient' => get_option('admin_email'),
            'body' => $body,
            'additional_headers' => 'Reply-To: [your-email]',
            'attachments' => '',
            'use_html' => false,
            'exclude_blank' => true,
        ];
    }

    /**
     * Meldungen auf Deutsch und in der Ansprache des Themes.
     *
     * @return array<string, string>
     */
    private static function defaultMessages(): array
    {
        return [
            'mail_sent_ok' => __('Vielen Dank, Deine Nachricht ist angekommen. Wir melden uns.', 'wp-starter'),
            'mail_sent_ng' => __('Die Nachricht konnte nicht gesendet werden. Bitte versuch es später erneut.', 'wp-starter'),
            'validation_error' => __('Bitte prüf die markierten Felder.', 'wp-starter'),
            'spam' => __('Die Nachricht wurde als Spam eingestuft und nicht gesendet.', 'wp-starter'),
            'accept_terms' => __('Bitte stimm der Verarbeitung deiner Daten zu.', 'wp-starter'),
            'invalid_required' => __('Dieses Feld ist ein Pflichtfeld.', 'wp-starter'),
            'invalid_email' => __('Diese E-Mail-Adresse sieht nicht richtig aus.', 'wp-starter'),
        ];
    }
}
