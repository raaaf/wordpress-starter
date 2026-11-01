<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\TeamMember;

final class TeamMemberTest extends TestCase
{
    public function testWhitespaceOnlyTextIsEmpty(): void
    {
        $this->assertSame('', TeamMember::text("  \n\t "));
        $this->assertSame('Anna', TeamMember::text('  Anna '));
        $this->assertSame('', TeamMember::text(null));
    }

    public function testMemberWithOnlyBlanksAndNoImageIsEmpty(): void
    {
        $this->assertTrue(TeamMember::isEmpty(['name' => ' ', 'position' => "\n", 'image' => 0]));
    }

    public function testMemberWithAnyContentIsNotEmpty(): void
    {
        $this->assertFalse(TeamMember::isEmpty(['name' => 'Anna']));
        $this->assertFalse(TeamMember::isEmpty(['position' => 'Leitung']));
        $this->assertFalse(TeamMember::isEmpty(['image' => 12]));
    }
}
