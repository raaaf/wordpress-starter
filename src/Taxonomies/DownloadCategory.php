<?php

declare(strict_types=1);

namespace WordpressStarter\Taxonomies;

class DownloadCategory extends AbstractTaxonomy
{
    protected static string $taxonomy         = 'download_category';
    protected static string $singular         = 'Dokumentenkategorie';
    protected static string $plural           = 'Dokumentenkategorien';
    protected static array $postTypes        = ['member_download'];
    protected static bool $hierarchical     = false;
    protected static bool $public           = false;
    protected static bool $showInRest       = false;
    protected static array|false $rewrite     = false;
}
