<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Pins the kses form-control allowance and the ACF field validators.
 */
final class AcfValidatorsTest extends TestCase
{
    public function testFormControlTagsAllowedOnFrontend(): void
    {
        $GLOBALS['wp_mock_is_admin'] = false;

        $tags = AcfServiceProvider::allowFormControlTags([], 'post');

        $this->assertArrayHasKey('form', $tags);
        $this->assertArrayHasKey('input', $tags);
    }

    public function testFormControlTagsDeniedInAdminWithoutUnfilteredHtml(): void
    {
        $GLOBALS['wp_mock_is_admin'] = true;
        $GLOBALS['wp_mock_current_user_can']['unfiltered_html'] = false;

        $tags = AcfServiceProvider::allowFormControlTags([], 'post');

        $this->assertArrayNotHasKey('form', $tags);
    }

    public function testFormControlTagsAllowedInAdminWithUnfilteredHtml(): void
    {
        $GLOBALS['wp_mock_is_admin'] = true;
        $GLOBALS['wp_mock_current_user_can']['unfiltered_html'] = true;

        $tags = AcfServiceProvider::allowFormControlTags([], 'post');

        $this->assertArrayHasKey('form', $tags);
    }

    public function testFormControlTagsUntouchedOutsidePostContext(): void
    {
        $GLOBALS['wp_mock_is_admin'] = false;
        $input = ['p' => ['class' => true]];

        $this->assertSame($input, AcfServiceProvider::allowFormControlTags($input, 'data'));
    }

    public function testValidateUrlAcceptsAbsoluteAndProtocolRelative(): void
    {
        $this->assertTrue(AcfServiceProvider::validateUrl(true, 'https://example.com/x'));
        $this->assertTrue(AcfServiceProvider::validateUrl(true, '//example.com/x'));
    }

    public function testValidateUrlRejectsGarbage(): void
    {
        $this->assertIsString(AcfServiceProvider::validateUrl(true, 'not a url'));
    }

    public function testNewsletterActionUrlAcceptsEmptyAndHttps(): void
    {
        $this->assertTrue(AcfServiceProvider::validateNewsletterActionUrl(true, ''));
        $this->assertTrue(AcfServiceProvider::validateNewsletterActionUrl(true, 'https://any-provider.example/post'));
    }

    public function testNewsletterActionUrlRejectsHttpAndGarbage(): void
    {
        $this->assertIsString(AcfServiceProvider::validateNewsletterActionUrl(true, 'http://x.example'));
        $this->assertIsString(AcfServiceProvider::validateNewsletterActionUrl(true, 'not a url'));
    }

    public function testNewsletterActionUrlKeepsEarlierError(): void
    {
        $this->assertSame('frueherer Fehler', AcfServiceProvider::validateNewsletterActionUrl('frueherer Fehler', 'https://any-provider.example/post'));
    }

    public function testContactFormIdAcceptsHashAndDigits(): void
    {
        $this->assertTrue(AcfServiceProvider::validateContactFormId(true, 'a1b2c3d'));
    }

    public function testContactFormIdRejectsShortcodeBreakout(): void
    {
        $this->assertIsString(AcfServiceProvider::validateContactFormId(true, '1][x]'));
    }

    public function testValidateEmail(): void
    {
        $this->assertTrue(AcfServiceProvider::validateEmail(true, 'a@b.de'));
        $this->assertIsString(AcfServiceProvider::validateEmail(true, 'kein-mail'));
    }

    /**
     * Die Validator-Hooks haengen an Feld-Keys; stimmen sie nicht mit den
     * Keys der echten Felddefinitionen ueberein, laeuft die Validierung still ins Leere.
     */
    public function testValidationHooksTargetKeysOfRealFieldDefinitions(): void
    {
        $GLOBALS['wp_mock_hooks']['filters'] = [];
        $method = new \ReflectionMethod(AcfServiceProvider::class, 'registerValidationHooks');
        $method->invoke($this->createStub(AcfServiceProvider::class));

        $hooked = [
            'validateNewsletterActionUrl' => 'flex_newsletter',
            'validateNewsletterEmailField' => 'flex_newsletter',
            'validateContactFormId' => 'flex_contact_form',
        ];
        $definitions = [
            'flex_newsletter' => FieldDefinitions::newsletterFields('flex_newsletter'),
            'flex_contact_form' => FieldDefinitions::contactFormFields('flex_contact_form'),
        ];

        foreach ($hooked as $validator => $prefix) {
            $keys = [];
            foreach ($GLOBALS['wp_mock_hooks']['filters'] as $hook => $entries) {
                foreach ($entries as $entry) {
                    if ($entry['callback'] === [AcfServiceProvider::class, $validator]) {
                        $keys[] = substr($hook, strlen('acf/validate_value/key='));
                    }
                }
            }

            $this->assertCount(1, $keys, $validator);
            $this->assertContains($keys[0], array_column($definitions[$prefix], 'key'), $validator);
        }
    }
}
