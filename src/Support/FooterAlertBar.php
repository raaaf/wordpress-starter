<?php

declare(strict_types=1);

namespace WordpressStarter\Support;

use WordpressStarter\Acf\Fields;

class FooterAlertBar
{
    /**
     * Returns only the alerts visible on the current page, render-ready.
     *
     * @return array<int, array{text: string, dismissible: bool, storage_key: string}>
     */
    public static function getVisibleAlerts(): array
    {
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
        $html = (string) preg_replace('#<h[1-6](?:\s[^>]*)?>#i', '<p><strong>', $html);

        return (string) preg_replace('#</h[1-6]>#i', '</strong></p>', $html);
    }
}
