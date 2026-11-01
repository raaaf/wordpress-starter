<?php

declare(strict_types=1);

namespace WordpressStarter\Support;

use enshrined\svgSanitize\Sanitizer;

/**
 * SVG sanitization using enshrined/svg-sanitize, shared by uploads
 * (MediaServiceProvider) and imported child-theme logos (MediaImport).
 *
 * Parses the XML and removes dangerous elements and attributes, rather than
 * using regex.
 *
 * @see https://github.com/darylldoyle/svg-sanitizer
 */
final class SvgSanitizer
{
    private function __construct() {}

    /** Sanitized markup, or false when the content is no parseable SVG. */
    public static function sanitize(string $content): string|false
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(false); // Keep the XML declaration

        // Fail closed: callers reject false instead of storing unsanitized content.
        return $sanitizer->sanitize($content);
    }
}
