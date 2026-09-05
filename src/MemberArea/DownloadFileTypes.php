<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

/**
 * Single source of truth for recognised download file extensions, their MIME
 * types, and their badge variant in the member downloads listing.
 */
final class DownloadFileTypes
{
    private const TYPES = [
        'pdf' => ['mime' => 'application/pdf', 'variant' => 'error'],
        'doc' => ['mime' => 'application/msword', 'variant' => 'brand'],
        'docx' => ['mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'variant' => 'brand'],
        'xls' => ['mime' => 'application/vnd.ms-excel', 'variant' => 'success'],
        'xlsx' => ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'variant' => 'success'],
        'zip' => ['mime' => 'application/zip', 'variant' => 'warning'],
    ];

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return array_keys(self::TYPES);
    }

    public static function mimeFor(string $extension): string
    {
        return self::TYPES[strtolower($extension)]['mime'] ?? 'application/octet-stream';
    }

    public static function variantFor(string $extension): string
    {
        return self::TYPES[strtolower($extension)]['variant'] ?? 'gray';
    }
}
