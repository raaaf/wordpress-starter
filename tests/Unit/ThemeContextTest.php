<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\ThemeContext;

final class ThemeContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_mock_template'] = 'wordpress-starter-theme';
        $GLOBALS['wp_mock_options'] = [];
        $GLOBALS['wp_mock_transients'] = [];
        ThemeContext::reset();
    }

    public function testSlugReturnsTemplateSlug(): void
    {
        $this->assertSame('wordpress-starter-theme', ThemeContext::slug());
    }

    public function testPrefixConvertsDashesToUnderscores(): void
    {
        $this->assertSame('wordpress_starter_theme', ThemeContext::prefix());
    }

    public function testOptionKeyPrefixesWithThemeSlug(): void
    {
        $this->assertSame(
            'wordpress_starter_theme_content_setup_complete',
            ThemeContext::optionKey('content_setup_complete')
        );
    }

    public function testIsActiveOnCurrentSiteReturnsTrueWhenTemplateMatches(): void
    {
        $this->assertTrue(ThemeContext::isActiveOnCurrentSite());
    }

    public function testIsActiveOnCurrentSiteReturnsFalseForDifferentTemplate(): void
    {
        $GLOBALS['wp_mock_template'] = 'other-theme';
        ThemeContext::reset();
        $this->assertFalse(ThemeContext::isActiveOnCurrentSite());
    }

    public function testMigrateCopiesOldKeysToNewKeys(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;
        $GLOBALS['wp_mock_options']['wp_starter_theme_activated'] = true;

        ThemeContext::migrate();

        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_content_setup_complete')
        );
        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_theme_activated')
        );
    }

    public function testMigrateDoesNotOverwriteExistingNewKey(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = 'old_value';
        $GLOBALS['wp_mock_options']['wordpress_starter_theme_content_setup_complete'] = 'new_value';

        ThemeContext::migrate();

        $this->assertSame(
            'new_value',
            get_option('wordpress_starter_theme_content_setup_complete')
        );
    }

    public function testMigrateIsIdempotent(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;

        ThemeContext::migrate();
        ThemeContext::migrate();

        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_content_setup_complete')
        );
    }

    public function testMigrateSetsCompletionFlag(): void
    {
        ThemeContext::migrate();

        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_migration_done')
        );
    }

    public function testMigrateDoesNotDeleteOldKeys(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;

        ThemeContext::migrate();

        $this->assertTrue(
            (bool) get_option('wp_starter_content_setup_complete')
        );
    }
}
