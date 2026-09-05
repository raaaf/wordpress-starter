<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Application;
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

    public function testAllowFormControlTagsNeverAddsOnHandlersOrFormaction(): void
    {
        // The filter lists attributes explicitly (see docblock); on* handlers
        // and formaction must never appear in the resulting allow-list, since
        // core's own allowlist merge would otherwise let a script back in.
        $tags = AcfServiceProvider::allowFormControlTags([], 'post');

        foreach (['form', 'input'] as $tag) {
            $this->assertArrayNotHasKey('onclick', $tags[$tag], "<{$tag}> must not allow onclick");
            $this->assertArrayNotHasKey('onsubmit', $tags[$tag], "<{$tag}> must not allow onsubmit");
            $this->assertArrayNotHasKey('formaction', $tags[$tag], "<{$tag}> must not allow formaction");
        }
    }

    public function testAllowFormControlTagsIsIdempotentWhenAppliedTwice(): void
    {
        $once = AcfServiceProvider::allowFormControlTags([], 'post');
        $twice = AcfServiceProvider::allowFormControlTags($once, 'post');

        $this->assertSame($once, $twice);
    }

    public function testAllowMediaSourceTagReturnsInputUnchangedForNonStringContext(): void
    {
        $input = ['video' => ['src' => true]];

        $tags = AcfServiceProvider::allowMediaSourceTag($input, '');

        $this->assertSame($input, $tags);
        $this->assertArrayNotHasKey('source', $tags);
    }

    /**
     * Renders the real @kses directive through a temp Blade view. A
     * compileString()-only check (no eval) was tried first and rejected:
     * phpcs forbids eval() project-wide (Security.eval sniff), so executing
     * the compiled directive output without a real Blade render is not
     * available here. The temp file is scoped to a per-run unique name under
     * a mode-0700 directory in the system temp dir and removed immediately
     * after the render.
     */
    public function testKsesDirectiveSanitizesHostilePayloadAndKeepsAllowedMarkup(): void
    {
        Application::getInstance()->boot();

        $factory = blade();
        $dir = sys_get_temp_dir() . '/wp-starter-test-' . bin2hex(random_bytes(8));
        mkdir($dir, 0700);
        file_put_contents($dir . '/kses-probe.blade.php', '@kses($html)');
        chmod($dir . '/kses-probe.blade.php', 0600);
        $factory->getFinder()->addLocation($dir);

        try {
            $out = $factory->make('kses-probe', [
                'html' => '<script>alert(1)</script><img src="x" onerror="alert(1)">'
                    . '<a href="javascript:alert(1)">link</a><strong>ok</strong>',
            ])->render();
        } finally {
            unlink($dir . '/kses-probe.blade.php');
            rmdir($dir);
        }

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringContainsString('<strong>ok</strong>', $out);
    }
}
