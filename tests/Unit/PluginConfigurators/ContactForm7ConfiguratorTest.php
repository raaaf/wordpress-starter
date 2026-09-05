<?php

declare(strict_types=1);

namespace Tests\Unit\PluginConfigurators;

use Tests\Support\TestCase;
use WordpressStarter\PluginConfigurators\ContactForm7Configurator;

/**
 * Tests for the server-side spam heuristics in ContactForm7Configurator,
 * in particular the JS-token check that replaced the signed-timestamp
 * time-trap (inert under full-page caching, see docs/SECURITY.md).
 */
final class ContactForm7ConfiguratorTest extends TestCase
{
    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    public function testDetectSpamFlagsSubmissionWithoutJsToken(): void
    {
        $_POST = [
            'your-website' => '',
            '_wpcf7_js_token' => '',
        ];

        $result = ContactForm7Configurator::detectSpam(false);

        $this->assertTrue($result);
    }

    public function testDetectSpamFlagsSubmissionMissingJsTokenField(): void
    {
        $_POST = [
            'your-website' => '',
        ];

        $result = ContactForm7Configurator::detectSpam(false);

        $this->assertTrue($result);
    }

    public function testDetectSpamPassesSubmissionWithJsTokenPresent(): void
    {
        $_POST = [
            'your-website' => '',
            '_wpcf7_js_token' => 'ok',
            'your-message' => 'Hallo, ich habe eine Frage zu Ihrem Angebot.',
        ];

        $result = ContactForm7Configurator::detectSpam(false);

        $this->assertFalse($result);
    }

    public function testDetectSpamStillFlagsHoneypotEvenWithJsToken(): void
    {
        $_POST = [
            'your-website' => 'http://spam.example',
            '_wpcf7_js_token' => 'ok',
        ];

        $result = ContactForm7Configurator::detectSpam(false);

        $this->assertTrue($result);
    }

    public function testInjectSpamTrapsAddsEmptyHiddenJsTokenField(): void
    {
        $output = ContactForm7Configurator::injectSpamTraps('<p>form fields</p>');

        $this->assertStringContainsString(
            '<input type="hidden" name="_wpcf7_js_token" value="">',
            $output,
        );
    }

    public function testInjectSpamTrapsAddsHoneypotField(): void
    {
        $output = ContactForm7Configurator::injectSpamTraps('<p>form fields</p>');

        $this->assertStringContainsString('name="your-website"', $output);
    }
}
