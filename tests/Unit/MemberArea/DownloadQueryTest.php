<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use ReflectionMethod;
use Tests\Support\TestCase;
use Tests\Support\WpJsonResponseException;
use WordpressStarter\MemberArea\DownloadQuery;

/**
 * Covers the member_area_active guard added to DownloadQuery::handle(): the
 * download listing must refuse to run while the member area is switched off,
 * even for an already-authenticated administrator.
 */
final class DownloadQueryTest extends TestCase
{
    public function testHandleRejectsRequestWhenMemberAreaInactive(): void
    {
        $this->setMockField('member_area_active', false, 'option');
        $GLOBALS['wp_mock_current_user_id'] = 1;
        $GLOBALS['wp_mock_current_user_can']['manage_options'] = true;

        try {
            DownloadQuery::handle();
            $this->fail('Expected wp_send_json_error to be called.');
        } catch (WpJsonResponseException $exception) {
            $this->assertFalse($exception->success);
            $this->assertSame(404, $exception->statusCode);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildItems(array $posts): array
    {
        $reflection = new ReflectionMethod(DownloadQuery::class, 'buildItems');
        $reflection->setAccessible(true);

        return $reflection->invoke(null, $posts);
    }

    public function testBuildItemsDecodesHtmlEntitiesInCategoryLabel(): void
    {
        $GLOBALS['wp_mock_terms']['download_category'] = [
            (object) ['slug' => 'bilder-logos', 'name' => 'Bilder &amp; Logos'],
        ];
        $GLOBALS['wp_mock_post_terms'][10]['download_category'] = [
            (object) ['slug' => 'bilder-logos', 'name' => 'Bilder &amp; Logos'],
        ];
        $this->setMockField('download_available', true, 10);
        $this->setMockField('download_source_type', 'external', 10);
        $this->setMockField('download_external_url', 'https://example.com/file.pdf', 10);

        $post = (object) ['ID' => 10, 'post_title' => 'Test'];

        $items = $this->buildItems([$post]);

        $this->assertSame('Bilder & Logos', $items[0]['category_label']);
    }

    public function testBuildItemsMarksUploadUnavailableWhenAttachmentIsMissing(): void
    {
        $this->setMockField('download_available', true, 20);
        $this->setMockField('download_source_type', 'upload', 20);
        $this->setMockField('download_file', false, 20);

        $post = (object) ['ID' => 20, 'post_title' => 'Broken upload'];

        $items = $this->buildItems([$post]);

        $this->assertFalse($items[0]['available']);
    }

    public function testBuildItemsKeepsUploadAvailableWhenAttachmentExists(): void
    {
        $this->setMockField('download_available', true, 21);
        $this->setMockField('download_source_type', 'upload', 21);
        $this->setMockField('download_file', ['url' => 'https://example.com/f.pdf', 'filename' => 'f.pdf'], 21);

        $post = (object) ['ID' => 21, 'post_title' => 'Working upload'];

        $items = $this->buildItems([$post]);

        $this->assertTrue($items[0]['available']);
    }
}
