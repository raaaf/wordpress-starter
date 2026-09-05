<?php

declare(strict_types=1);

namespace Tests\Support;

use WordpressStarter\Taxonomies\AbstractTaxonomy;

/**
 * Minimal taxonomy fixture without a $requiredCapability override, used to
 * assert that the default (untouched) capability behaviour is preserved.
 */
final class PlainTaxonomyFixture extends AbstractTaxonomy
{
    protected static string $taxonomy = 'plain_taxonomy_fixture';

    protected static string $singular = 'Fixture';

    protected static string $plural = 'Fixtures';

    protected static array $postTypes = ['post'];
}
