<?php

declare(strict_types=1);

namespace Tests\Unit\PostTypes;

use Tests\Support\TestCase;
use WordpressStarter\PostTypes\AbstractPostType;
use WordpressStarter\PostTypes\Event;
use WordpressStarter\PostTypes\MemberDownload;
use WordpressStarter\PostTypes\Team;

/**
 * The labels used to hard-code feminine forms, which produced "Neue Dokument"
 * and "Zu dieser Dokument hochgeladen" in the admin. Each post type declares
 * its grammatical gender and the labels follow it.
 */
final class PostTypeLabelGenderTest extends TestCase
{
    public function testFeminineTypeUsesFeminineForms(): void
    {
        Event::registerPostType();

        $labels = $GLOBALS['wp_mock_post_types']['event']['labels'];

        $this->assertSame('Neue Veranstaltung', $labels['new_item']);
        $this->assertSame('Zu dieser Veranstaltung hochgeladen', $labels['uploaded_to_this_item']);
    }

    public function testNeuterTypeUsesNeuterForms(): void
    {
        MemberDownload::registerPostType();

        $labels = $GLOBALS['wp_mock_post_types']['member_download']['labels'];

        $this->assertSame('Neues Dokument', $labels['new_item']);
        $this->assertSame('Übergeordnetes Dokument:', $labels['parent_item_colon']);
    }

    public function testMasculineTypeUsesMasculineForms(): void
    {
        $type = new class() extends AbstractPostType {
            protected static string $postType = 'gender_probe_m';

            protected static string $singular = 'Beitrag';

            protected static string $plural = 'Beiträge';

            protected static string $genus = 'm';
        };
        $type::registerPostType();

        $labels = $GLOBALS['wp_mock_post_types']['gender_probe_m']['labels'];

        // add_new_item is accusative ("Neuen"), new_item stays nominative ("Neuer").
        $this->assertSame('Neuen Beitrag hinzufügen', $labels['add_new_item']);
        $this->assertSame('Neuer Beitrag', $labels['new_item']);
        $this->assertSame('Zu diesem Beitrag hochgeladen', $labels['uploaded_to_this_item']);
    }

    public function testTeamKeepsTheMenuNameWhilePluralReadsTeammitglieder(): void
    {
        Team::registerPostType();

        $labels = $GLOBALS['wp_mock_post_types']['team_member']['labels'];

        $this->assertSame('Team', $labels['menu_name']);
        $this->assertSame('Keine Teammitglieder gefunden', $labels['not_found']);
    }

    public function testCompoundLabelsUseColonForms(): void
    {
        Event::registerPostType();

        $labels = $GLOBALS['wp_mock_post_types']['event']['labels'];

        $this->assertSame('Archiv: Veranstaltungen', $labels['archives']);
        $this->assertSame('Listennavigation: Veranstaltungen', $labels['items_list_navigation']);
    }
}
