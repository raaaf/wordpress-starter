<?php

declare(strict_types=1);

namespace Tests\Unit\PostTypes;

use Tests\Support\AdminColumnsProbePostType;
use Tests\Support\TestCase;

/**
 * The adminColumns() definition holds translated labels and must not be evaluated before
 * `init` (WP 6.7+ just-in-time textdomain loading notice). Before init the
 * wiring is deferred to an `init` action; after init it happens immediately.
 */
final class AbstractPostTypeAdminColumnsInitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AdminColumnsProbePostType::$adminColumnsCalls = 0;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_test_did_action']);
        parent::tearDown();
    }

    public function testBeforeInitDefersEvaluationToInitAction(): void
    {
        $GLOBALS['wp_test_did_action']['init'] = 0;

        AdminColumnsProbePostType::wire();

        $this->assertSame(0, AdminColumnsProbePostType::$adminColumnsCalls);
        $this->assertNotEmpty($GLOBALS['wp_mock_hooks']['actions']['init'] ?? []);
        $this->assertArrayNotHasKey('manage_probe_posts_columns', $GLOBALS['wp_mock_hooks']['filters'] ?? []);

        foreach ($GLOBALS['wp_mock_hooks']['actions']['init'] as $hook) {
            ( $hook['callback'] )();
        }

        $this->assertSame(1, AdminColumnsProbePostType::$adminColumnsCalls);
        $this->assertNotEmpty($GLOBALS['wp_mock_hooks']['filters']['manage_probe_posts_columns'] ?? []);
    }

    public function testAfterInitWiresImmediately(): void
    {
        $GLOBALS['wp_test_did_action']['init'] = 1;

        AdminColumnsProbePostType::wire();

        $this->assertSame(1, AdminColumnsProbePostType::$adminColumnsCalls);
        $this->assertEmpty($GLOBALS['wp_mock_hooks']['actions']['init'] ?? []);
        $this->assertNotEmpty($GLOBALS['wp_mock_hooks']['filters']['manage_probe_posts_columns'] ?? []);
    }
}
