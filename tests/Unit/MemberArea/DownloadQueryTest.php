<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

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
}
