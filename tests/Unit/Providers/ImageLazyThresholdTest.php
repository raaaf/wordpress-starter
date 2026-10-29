<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\ImageServiceProvider;

/**
 * Only the first content image (the hero) may skip lazy loading.
 */
final class ImageLazyThresholdTest extends TestCase
{
    public function testOnlyTheFirstContentImageStaysEager(): void
    {
        ( new ImageServiceProvider() )->boot();

        $this->assertFilterAdded('wp_omit_loading_attr_threshold');
        $this->assertSame(1, apply_filters('wp_omit_loading_attr_threshold', 3));
    }
}
