<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\Options;

/**
 * The theme options menu links to the MCP plugin's page only while the
 * plugin is active (WPSMCP_VERSION).
 */
final class OptionsMcpLinkTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_submenu_pages']);
        parent::tearDown();
    }

    public function testNoLinkWithoutThePlugin(): void
    {
        $this->assertFalse(defined('WPSMCP_VERSION'));

        $this->invokeStaticMethod(Options::class, 'addMcpPluginLink');

        $this->assertArrayNotHasKey('admin_menu', $GLOBALS['wp_mock_hooks']['actions']);
    }

    public function testLinksToThePluginPageUnderTools(): void
    {
        $this->invokeStaticMethod(Options::class, 'addMcpPluginLink', [true]);
        $hook = $GLOBALS['wp_mock_hooks']['actions']['admin_menu'][0];
        ( $hook['callback'] )();

        $page = $GLOBALS['wp_mock_submenu_pages'][0];
        $this->assertSame(100, $hook['priority']);
        $this->assertSame('theme-options', $page['parent']);
        $this->assertSame('manage_options', $page['capability']);
        $this->assertSame('tools.php?page=wpsmcp', $page['menuSlug']);
    }
}
