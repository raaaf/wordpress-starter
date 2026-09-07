<?php

declare(strict_types=1);

namespace WordpressStarter\Support;

use WordpressStarter\Acf\Fields;
use WordpressStarter\Acf\PageSettings;

class FooterAlertBar
{
    /**
     * Returns only the alerts visible on the current page, render-ready.
     *
     * @return array<int, array{text: string, dismissible: bool, storage_key: string}>
     */
    public static function getVisibleAlerts(): array
    {
        if (PageSettings::shouldHideGlobalNotice()) {
            return [];
        }

        $alerts = Fields::option('footer_alerts', []);
        if (empty($alerts)) {
            return [];
        }

        $currentPageId = get_queried_object_id();
        $visible = [];

        foreach ($alerts as $index => $alert) {
            if (empty($alert['active'])) {
                continue;
            }

            $text = trim($alert['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $text = self::demoteHeadings($text);

            $visibility = $alert['visibility'] ?? 'all';
            $pageIds = array_map('intval', $alert['pages'] ?? []);

            if ($visibility === 'only' && !in_array($currentPageId, $pageIds, true)) {
                continue;
            }
            if ($visibility === 'except' && in_array($currentPageId, $pageIds, true)) {
                continue;
            }

            $visible[] = [
                'text' => $text,
                'dismissible' => !empty($alert['dismissible']),
                'storage_key' => 'footer_alert_' . md5($text . '_' . $index),
            ];
        }

        return $visible;
    }

    /**
     * Demotes headings in alert text to bold paragraphs.
     *
     * The WYSIWYG toolbar offers no heading formats, but content pasted from
     * Outlook or Word carries them in. The alert bar renders after the last
     * content section, so any heading there lands out of order and fails the
     * accessibility heading-order check.
     */
    public static function demoteHeadings(string $html): string
    {
        // Quote-aware attribute list, so a `>` inside a quoted attribute value
        // (e.g. `<h2 title="a > b">`) does not end the tag match early.
        $attributes = '(?:\s(?:"[^"]*"|\'[^\']*\'|[^>"\'])*)?';

        $html = (string) preg_replace_callback(
            '#<h[1-6]' . $attributes . '>(.*?)</h[1-6]>#is',
            static function (array $matches): string {
                $inner = $matches[1];

                // Any nested <strong> is stripped and the whole content is
                // re-wrapped in exactly one <strong>, so a partially bold
                // heading (e.g. `<strong>Part</strong> rest`) never ends up
                // with a nested <strong><strong>...
                $stripped = (string) preg_replace('#</?strong(?:\s[^>]*)?>#i', '', $inner);

                return '<p><strong>' . $stripped . '</strong></p>';
            },
            $html
        );

        // Content pasted in from Outlook/Word can carry unclosed heading tags
        // (e.g. `<h3>Titel<p>Text</p>`), which the pair-aware pass above never
        // matches. Demote any surviving opening/closing heading tag on its own.
        $html = (string) preg_replace('#<h[1-6]' . $attributes . '>#i', '<p><strong>', $html);
        $html = (string) preg_replace('#</h[1-6]>#i', '</strong></p>', $html);

        // An unmatched heading (e.g. only the opener survived) now leaves an
        // opening <p><strong> with no closing tag; wp_kses_post() does not
        // balance tags, so force_balance_tags() closes what is still open.
        return force_balance_tags($html);
    }
}
