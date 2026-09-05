<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Contract tests for x-input, x-checkbox, x-radio, x-select, x-textarea, x-toggle.
 *
 * Renders raw <x-...> component-tag strings through a throwaway view file so
 * the Blade component tag compiler resolves them exactly as callers use them.
 * See ButtonComponentTest for the pattern this reuses.
 */
final class FormComponentsTest extends TestCase
{
    private string $probeDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->probeDir = sys_get_temp_dir() . '/form-component-probe-' . uniqid();
        mkdir($this->probeDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->probeDir . '/*.blade.php') ?: []);
        rmdir($this->probeDir);
        parent::tearDown();
    }

    private function renderMarkup(string $markup): string
    {
        $view = 'probe-' . uniqid();
        file_put_contents($this->probeDir . '/' . $view . '.blade.php', $markup);

        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $finder = $factory->getFinder();
        $finder->addLocation(dirname(__DIR__, 3) . '/templates');
        $finder->addLocation($this->probeDir);

        return $factory->make($view, [])->render();
    }

    public function testInputForwardsMaxlengthPatternInputmodeAndReadonly(): void
    {
        $html = $this->renderMarkup(
            '<x-input name="code" maxlength="10" pattern="[0-9]+" inputmode="numeric" readonly />',
        );

        preg_match('/<input\b[^>]*>/', $html, $matches);
        $inputTag = $matches[0] ?? '';

        $this->assertStringContainsString('maxlength="10"', $inputTag);
        $this->assertStringContainsString('pattern="[0-9]+"', $inputTag);
        $this->assertStringContainsString('inputmode="numeric"', $inputTag);
        $this->assertMatchesRegularExpression('/\breadonly\b/', $inputTag);
    }

    public function testInputMergesCallerAriaDescribedbyWithHintIdInsteadOfDuplicating(): void
    {
        $html = $this->renderMarkup(
            '<x-input name="foo" label="Foo" hint="Hinweis" aria-describedby="external-note" />',
        );

        preg_match('/<input\b[^>]*>/', $html, $matches);
        $inputTag = $matches[0] ?? '';

        $this->assertSame(1, substr_count($inputTag, 'aria-describedby='));
        $this->assertMatchesRegularExpression('/aria-describedby="[^"]+-hint external-note"/', $inputTag);
    }

    public function testInputWithoutIdGetsUniqueIdMatchingLabelFor(): void
    {
        $htmlOne = $this->renderMarkup('<x-input name="foo" label="Foo" />');
        $htmlTwo = $this->renderMarkup('<x-input name="foo" label="Foo" />');

        preg_match('/for="([^"]+)"/', $htmlOne, $labelOne);
        preg_match('/<input\b[^>]*\bid="([^"]+)"/', $htmlOne, $inputOne);
        preg_match('/for="([^"]+)"/', $htmlTwo, $labelTwo);

        $this->assertSame($labelOne[1], $inputOne[1]);
        $this->assertNotSame($labelOne[1], $labelTwo[1]);
    }

    public function testCheckboxAndRadioMergeExtraAttributesOntoInput(): void
    {
        $checkboxHtml = $this->renderMarkup(
            '<x-checkbox name="terms" label="Accept" required data-x="1" />',
        );
        $radioHtml = $this->renderMarkup(
            '<x-radio name="plan" value="pro" label="Pro" required data-x="1" />',
        );

        preg_match('/<input\b[^>]*>/', $checkboxHtml, $checkboxInput);
        preg_match('/<input\b[^>]*>/', $radioHtml, $radioInput);

        $this->assertMatchesRegularExpression('/\brequired\b/', $checkboxInput[0] ?? '');
        $this->assertStringContainsString('data-x="1"', $checkboxInput[0] ?? '');
        $this->assertMatchesRegularExpression('/\brequired\b/', $radioInput[0] ?? '');
        $this->assertStringContainsString('data-x="1"', $radioInput[0] ?? '');
    }

    public function testRadioIdIsValidIdrefForNameAndValueWithSpaces(): void
    {
        $html = $this->renderMarkup(
            '<x-radio name="plan type" value="pro plus" label="Pro Plus" />',
        );

        preg_match('/<input\b[^>]*\bid="([^"]+)"/', $html, $matches);
        $id = $matches[1] ?? '';

        $this->assertNotSame('', $id);
        $this->assertDoesNotMatchRegularExpression('/\s/', $id);
    }

    public function testCheckboxIndeterminateScriptEmbedsIdJsonEncodedWithNonce(): void
    {
        $html = $this->renderMarkup(
            '<x-checkbox name="opt" label="Opt" indeterminate="true" />',
        );

        preg_match('/<input\b[^>]*\bid="([^"]+)"/', $html, $inputId);
        $id = $inputId[1] ?? '';

        $this->assertMatchesRegularExpression('/<script nonce="[^"]*">/', $html);
        $this->assertStringContainsString(json_encode($id), $html);
    }

    public function testSelectAssociatesLabelAndAriaDescribedbyWithHint(): void
    {
        $html = $this->renderMarkup(
            '<x-select name="country" label="Land" hint="Bitte waehlen" :options="[\'de\' => \'Deutschland\']" />',
        );

        preg_match('/for="([^"]+)"/', $html, $label);
        preg_match('/<select\b[^>]*\bid="([^"]+)"[^>]*\baria-describedby="([^"]+)"/', $html, $select);

        $this->assertNotEmpty($select);
        $this->assertSame($label[1], $select[1]);
        $this->assertSame($select[1] . '-hint', $select[2]);
        $this->assertStringContainsString('id="' . $select[2] . '"', $html);
    }

    public function testTextareaAssociatesLabelAndAriaDescribedbyWithError(): void
    {
        $html = $this->renderMarkup(
            '<x-textarea name="message" label="Nachricht" error-message="Pflichtfeld" />',
        );

        preg_match('/for="([^"]+)"/', $html, $label);
        preg_match('/<textarea\b[^>]*\bid="([^"]+)"[^>]*\baria-describedby="([^"]+)"/', $html, $textarea);

        $this->assertNotEmpty($textarea);
        $this->assertSame($label[1], $textarea[1]);
        $this->assertSame($textarea[1] . '-hint', $textarea[2]);
        $this->assertStringContainsString('Pflichtfeld', $html);
    }

    public function testToggleAriaDescribedbyMatchesHintId(): void
    {
        // Toggle wraps the <input> inside its <label> (no separate for/id
        // pairing to assert, unlike select/textarea/input), so only the
        // aria-describedby <-> hint id link is checked here.
        $html = $this->renderMarkup(
            '<x-toggle name="notify" label="Benachrichtigen" hint="Optional" />',
        );

        preg_match('/<input\b[^>]*\bid="([^"]+)"[^>]*\baria-describedby="([^"]+)"/', $html, $toggle);

        $this->assertNotEmpty($toggle);
        $this->assertSame($toggle[1] . '-hint', $toggle[2]);
        $this->assertStringContainsString('id="' . $toggle[2] . '"', $html);
    }
}
