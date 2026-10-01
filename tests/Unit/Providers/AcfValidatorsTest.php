<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Pins the kses form-control allowance and the ACF field validators.
 */
final class AcfValidatorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_mock_current_user_can'] = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['wp_mock_current_user_can'] = [];
        parent::tearDown();
    }

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

    public function testNewsletterActionUrlAcceptsEmptyAndProvider(): void
    {
        $this->assertTrue(AcfServiceProvider::validateNewsletterActionUrl(true, ''));
        $this->assertTrue(AcfServiceProvider::validateNewsletterActionUrl(true, 'https://abc.us21.list-manage.com/subscribe/post'));
    }

    public function testNewsletterActionUrlRejectsForeignHost(): void
    {
        $this->assertIsString(AcfServiceProvider::validateNewsletterActionUrl(true, 'https://evil.example/post'));
    }

    public function testNewsletterActionUrlKeepsEarlierError(): void
    {
        $this->assertSame('frueherer Fehler', AcfServiceProvider::validateNewsletterActionUrl('frueherer Fehler', 'https://abc.us21.list-manage.com/subscribe/post'));
    }

    public function testValidateEmail(): void
    {
        $this->assertTrue(AcfServiceProvider::validateEmail(true, 'a@b.de'));
        $this->assertIsString(AcfServiceProvider::validateEmail(true, 'kein-mail'));
    }
}
