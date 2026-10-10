<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;
use WordpressStarter\Helpers\JumpMenu;

/**
 * Tests for JumpMenu::isHidden(), which reads the RAW (unformatted) value of
 * the ACF true_false field `show_in_jump_menu`. Only an explicit off hides a
 * section ('' from update_field/seeder, '0' from the admin form). ACF injects
 * the default (1) for unsaved fields; null means the field is not registered
 * on the layout and must stay in the menu.
 */
final class JumpMenuTest extends TestCase
{
    #[DataProvider('explicitOffValues')]
    public function testIsHiddenIsTrueOnlyForExplicitOff(mixed $raw): void
    {
        $this->assertTrue(JumpMenu::isHidden($raw));
    }

    /** @return array<string, array{0: mixed}> */
    public static function explicitOffValues(): array
    {
        return [
            'int 0' => [0],
            'string 0' => ['0'],
            'bool false' => [false],
            'empty string (update_field/seeder off)' => [''],
        ];
    }

    #[DataProvider('visibleValues')]
    public function testIsHiddenIsFalseForEverythingElse(mixed $raw): void
    {
        $this->assertFalse(JumpMenu::isHidden($raw));
    }

    /** @return array<string, array{0: mixed}> */
    public static function visibleValues(): array
    {
        return [
            'null (field not registered)' => [null],
            'int 1' => [1],
            'string 1' => ['1'],
            'bool true' => [true],
        ];
    }
}
