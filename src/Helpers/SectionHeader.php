<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Reads the section-header fields every layout with an optional header shares.
 *
 * Ten layouts carried the identical five lines. They are pure field reads and
 * produce no markup, so consolidating them cannot change what a section looks
 * like -- the compiled output of each template was compared before and after.
 *
 * Deliberately NOT extended to the surrounding grid wrapper or the image-card
 * body, although those look duplicated too: their gap and their label placement
 * genuinely differ between the two-, three- and four-column variants
 * (gap xl/xl/md, label above the image vs inside the text block). Folding those
 * together would quietly unify values that were chosen apart, and would change
 * every affected section on every client site.
 *
 * @see templates/components/section-header.blade.php
 */
final class SectionHeader
{
    private function __construct()
    {
    }

    /**
     * @return array{chip: ?string, headline: ?string, description: ?string, alignment: string}
     */
    public static function fields(): array
    {
        $show = get_sub_field('show_section_header');

        return [
            'chip' => $show ? get_sub_field('section_chip') : null,
            'headline' => $show ? Text::lineBreaks(get_sub_field('section_headline')) : null,
            'description' => $show ? Text::lineBreaks(get_sub_field('section_description')) : null,
            'alignment' => $show ? ( get_sub_field('section_alignment') ?: 'center' ) : 'center',
        ];
    }
}
