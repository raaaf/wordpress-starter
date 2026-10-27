<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use ReflectionClass;
use Tests\Support\TestCase;
use WordpressStarter\MemberArea\Auth;
use WP_Error;

/**
 * Tests for WordpressStarter\MemberArea\Auth, focused on the two audit fixes:
 * clearing the real WordPress auth cookie when wp_signon() succeeds but the
 * account's role is not allowed into the member area, and rejecting login
 * while the member area is switched off.
 */
final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Auth caches auth mode/cookie ttl/shared password in static
        // properties that resetAllMocks() does not touch; reset them so one
        // test's field values cannot leak into the next.
        $reflection = new ReflectionClass(Auth::class);
        foreach (['cachedAuthMode', 'cachedCookieTtl', 'cachedSharedPassword'] as $property) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue(null, false);
        }

        $GLOBALS['wp_mock_clear_auth_cookie_called'] = false;
        $GLOBALS['wp_mock_current_user_roles'] = [];
    }

    public function testLoginRejectedWhenMemberAreaInactive(): void
    {
        $this->setMockField('member_auth_mode', 'wordpress', 'option'); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- ACF field value, not prose
        $this->setMockField('member_area_active', false, 'option');

        $result = Auth::login('someuser', 'irrelevant');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('member_area_inactive', $result->get_error_code());
        $this->assertFalse($GLOBALS['wp_mock_clear_auth_cookie_called']);
    }

    public function testLoginClearsAuthCookieWhenRoleNotAllowed(): void
    {
        $this->setMockField('member_auth_mode', 'wordpress', 'option'); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- ACF field value, not prose
        $this->setMockField('member_allowed_roles', ['editor'], 'option');
        $GLOBALS['wp_mock_signon_result'] = (object) ['ID' => 5];
        $GLOBALS['wp_mock_current_user_roles'] = ['subscriber'];

        $result = Auth::login('someuser', 'correct-password');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('member_login_role_not_allowed', $result->get_error_code());
        $this->assertTrue($GLOBALS['wp_mock_clear_auth_cookie_called']);
        $this->assertSame(0, $GLOBALS['wp_mock_current_user_id']);
    }

    public function testLoginSucceedsWhenRoleAllowed(): void
    {
        $this->setMockField('member_auth_mode', 'wordpress', 'option'); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- ACF field value, not prose
        $this->setMockField('member_allowed_roles', ['editor'], 'option');
        $GLOBALS['wp_mock_signon_result'] = (object) ['ID' => 5];
        $GLOBALS['wp_mock_current_user_roles'] = ['editor'];

        $result = Auth::login('someuser', 'correct-password');

        $this->assertTrue($result);
        $this->assertFalse($GLOBALS['wp_mock_clear_auth_cookie_called']);
        $this->assertSame(5, $GLOBALS['wp_mock_current_user_id']);
    }

    public function testEmptyAllowedRolesDeniesNonAdmin(): void
    {
        $this->loginAs(['subscriber']);
        $this->setMockField('member_allowed_roles', [], 'option');

        $this->assertFalse(Auth::isAuthenticated());
    }

    public function testEmptyAllowedRolesStillAllowsAdministrator(): void
    {
        $this->loginAs(['administrator']);
        $this->setMockField('member_allowed_roles', [], 'option');
        $GLOBALS['wp_mock_current_user_can'] = ['manage_options' => true];

        $this->assertTrue(Auth::isAuthenticated());
    }

    public function testMultisiteRequiresMembershipOfCurrentBlog(): void
    {
        $this->loginAs(['subscriber']);
        $this->setMockField('member_allowed_roles', ['subscriber'], 'option');
        $GLOBALS['wp_mock_is_multisite'] = true;
        $GLOBALS['wp_mock_is_user_member_of_blog'] = false;

        $this->assertFalse(Auth::isAuthenticated());

        $GLOBALS['wp_mock_is_user_member_of_blog'] = true;
        $this->assertTrue(Auth::isAuthenticated());
    }

    /**
     * @param array<int, string> $roles
     */
    private function loginAs(array $roles): void
    {
        $reflection = new \ReflectionClass(Auth::class);
        foreach (['cachedAuthMode' => null, 'authModeCached' => false] as $property => $value) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setValue(null, $prop->getType()?->allowsNull() ? null : false);
            }
        }

        $this->setMockField('member_auth_mode', 'wordpress', 'option'); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- ACF field value, not prose
        $GLOBALS['wp_mock_current_user_id'] = 7;
        $GLOBALS['wp_mock_is_user_logged_in'] = true;
        $GLOBALS['wp_mock_current_user_roles'] = $roles;
    }
}
