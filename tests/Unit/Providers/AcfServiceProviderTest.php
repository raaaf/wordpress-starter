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

    public function testAllowFormControlTagsAddsFormMarkupInPostContext(): void
    {
        $tags = AcfServiceProvider::allowFormControlTags([], 'post');

        foreach (['form', 'input', 'select', 'option', 'optgroup'] as $tag) {
            $this->assertArrayHasKey($tag, $tags, "<{$tag}> must survive post kses");
        }

        // Without these a Contact Form 7 shortcode in a WYSIWYG field renders
        // labels and nothing to type into.
        $this->assertArrayHasKey('action', $tags['form']);
        $this->assertArrayHasKey('method', $tags['form']);
        $this->assertArrayHasKey('type', $tags['input']);
        $this->assertArrayHasKey('name', $tags['input']);
        $this->assertArrayHasKey('value', $tags['input']);
    }

    public function testAllowFormControlTagsKeepsGlobalAttributesPerTag(): void
    {
        // Tags added through the filter do not inherit core's global
        // attributes, so each one has to carry them itself.
        $tags = AcfServiceProvider::allowFormControlTags([], 'post');

        foreach (['form', 'input', 'select', 'option', 'optgroup'] as $tag) {
            $this->assertArrayHasKey('class', $tags[$tag], "<{$tag}> loses its class otherwise");
            $this->assertArrayHasKey('aria-required', $tags[$tag]);
        }
    }

    public function testAllowFormControlTagsWidensTextareaWithoutDroppingCoreAttributes(): void
    {
        $tags = AcfServiceProvider::allowFormControlTags(
            ['textarea' => ['cols' => true, 'rows' => true]],
            'post'
        );

        $this->assertArrayHasKey('cols', $tags['textarea']);
        $this->assertArrayHasKey('placeholder', $tags['textarea']);
        $this->assertArrayHasKey('class', $tags['textarea']);
    }

    public function testAllowFormControlTagsLeavesOtherContextsUnchanged(): void
    {
        $input = ['p' => []];

        $tags = AcfServiceProvider::allowFormControlTags($input, 'pre_user_description');

        $this->assertSame($input, $tags);
        $this->assertArrayNotHasKey('form', $tags);
    }
}
