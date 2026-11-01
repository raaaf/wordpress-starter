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

    private const PLUGIN_HONEYPOT = '<span id="wpcf7-x-wrapper" class="wpcf7-form-control-wrap cf7apps-honeypot-wrap birthplace-676-wrap" >'
        . '<label for="wpcf7-x-field" class="hp-message">Bitte lasse dieses Feld leer.</label>'
        . '<input id="wpcf7-x-field"  class="wpcf7-form-control wpcf7-text" type="text" name="birthplace-676" value="" autocomplete="new-password" tabindex="1000" data-cf7apps-tabindex="1000" /></span>';

    public function testHoneypotLosesPositiveTabindexAndIsHiddenFromScreenReaders(): void
    {
        $output = ContactForm7Configurator::hideHoneypotFromAssistiveTech(self::PLUGIN_HONEYPOT);

        $this->assertStringNotContainsString('tabindex="1000"', $output);
        $this->assertStringNotContainsString('data-cf7apps-tabindex', $output);
        $this->assertStringContainsString('tabindex="-1" aria-hidden="true" />', $output);
        $this->assertStringContainsString('birthplace-676-wrap" aria-hidden="true">', $output);
    }

    public function testOtherFieldsStayUntouchedByHoneypotHiding(): void
    {
        $other = '<span class="wpcf7-form-control-wrap"><input type="text" name="plz" tabindex="3"></span>';

        $this->assertSame($other, ContactForm7Configurator::hideHoneypotFromAssistiveTech($other));
    }

    public function testDefaultFormValidatesAcceptanceOnSubmit(): void
    {
        $this->assertSame('acceptance_as_validation: on', ContactForm7Configurator::defaultAdditionalSettings());
    }
}
