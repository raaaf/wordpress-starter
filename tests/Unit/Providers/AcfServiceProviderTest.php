<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Tests for the post-kses <source> allowlist filter added to AcfServiceProvider.
 *
 * Core's post-context kses allowlist permits <video>/<audio> but not <source>,
 * so wp_kses_post() strips the sources emitted by wp_video_shortcode() and
 * wp_audio_shortcode(). The filter logic is a pure static method so it can be
 * exercised directly without a WordPress runtime.
 */
final class AcfServiceProviderTest extends TestCase
{
    public function testAllowMediaSourceTagAddsSourceInPostContext(): void
    {
        $tags = AcfServiceProvider::allowMediaSourceTag(['video' => ['src' => true]], 'post');

        $this->assertArrayHasKey('source', $tags);
        $this->assertSame(['src' => true, 'type' => true], $tags['source']);
        $this->assertArrayHasKey('video', $tags);
        $this->assertSame(['src' => true], $tags['video']);
    }

    public function testAllowMediaSourceTagLeavesOtherContextsUnchanged(): void
    {
        $input = ['video' => ['src' => true]];

        $tags = AcfServiceProvider::allowMediaSourceTag($input, 'pre_user_description');

        $this->assertSame($input, $tags);
        $this->assertArrayNotHasKey('source', $tags);
    }
}
