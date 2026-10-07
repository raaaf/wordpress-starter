<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;

/**
 * Das Layout member_downloads wird in der Auswahl nur auf Mitgliederseiten
 * angeboten. Die Seite kommt im Editor aus der URL, beim Speichern aus
 * `post_ID` und bei ACFs Ajax-Aufrufen (auch ACF Extendeds asynchronem
 * Layout-Laden) aus `post_id`. Fehlt der letzte Weg, hielte der Filter eine
 * Mitgliederseite fuer eine normale und das nachgeladene Layout kaeme leer zurueck.
 */
final class FlexibleContentMemberDownloadsFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FlexibleContent::resetLayoutCache();
        FlexibleContent::register();
        $this->setIsAdmin(true);
        $this->setMockField('page_is_member_area', true, 42);
        $this->setMockField('page_is_member_area', false, 43);
    }

    protected function tearDown(): void
    {
        unset($_POST['post_id']);
        FlexibleContent::resetLayoutCache();
        parent::tearDown();
    }

    /**
     * @return array<int, string>
     */
    private function offeredFor(string $filterKey, int $postId): array
    {
        $_POST['post_id'] = (string) $postId;

        // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        $field = apply_filters("acf/load_field/key={$filterKey}", [
            'layouts' => [['name' => 'hero'], ['name' => 'member_downloads']],
        ]);

        return array_column($field['layouts'], 'name');
    }

    public function testAjaxPostIdOfAMemberPageKeepsMemberDownloads(): void
    {
        $this->assertContains('member_downloads', $this->offeredFor('field_page_sections', 42));
        $this->assertContains('member_downloads', $this->offeredFor(FlexibleContent::NESTED_MODULES_KEY, 42));
    }

    public function testAjaxPostIdOfAPlainPageHidesMemberDownloads(): void
    {
        $this->assertNotContains('member_downloads', $this->offeredFor('field_page_sections', 43));
        $this->assertNotContains('member_downloads', $this->offeredFor(FlexibleContent::NESTED_MODULES_KEY, 43));
    }
}
