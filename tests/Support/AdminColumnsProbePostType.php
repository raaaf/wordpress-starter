<?php

declare(strict_types=1);

namespace Tests\Support;

use WordpressStarter\PostTypes\AbstractPostType;

/**
 * Post type fixture that counts how often adminColumns() is evaluated, used to
 * pin that the translated column labels are not built before `init`.
 */
final class AdminColumnsProbePostType extends AbstractPostType
{
    public static int $adminColumnsCalls = 0;

    protected static string $postType = 'probe';

    public static function wire(): void
    {
        self::registerAdminColumns();
    }

    protected static function adminColumns(): array
    {
        ++self::$adminColumnsCalls;

        return [
            'probe_col' => ['label' => 'Probe', 'render' => static function (int $postId): void {}],
        ];
    }
}
