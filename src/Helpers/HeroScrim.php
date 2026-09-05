<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

class HeroScrim
{
    /**
     * Build the radial-gradient scrim CSS for the hero background variant.
     *
     * The scrim always darkens, in both colour schemes: a light veil over the
     * image pulls every motif towards pastel and still guarantees no
     * readability, since it depends on the brightest spot of the image.
     * Darkening plus light text is the usual solution (NN/g, Smashing):
     * 40 to 60 percent black for white text.
     *
     * Not a flat veil but a gradient, strongest behind the text and fading
     * towards the corners. The image keeps its colour where there is no text.
     *
     * @param int $overlayOpacity 0-100, the ACF field value
     */
    public static function gradient(int $overlayOpacity): string
    {
        $overlayOpacityCss = $overlayOpacity / 100;
        $scrimMitte = min(0.85, $overlayOpacityCss * 0.75);
        $scrimAussen = round($scrimMitte * 0.55, 2);

        return sprintf(
            'radial-gradient(ellipse 90%% 75%% at 50%% 50%%, rgba(0,0,0,%s) 0%%, rgba(0,0,0,%s) 55%%, rgba(0,0,0,%s) 100%%)',
            round($scrimMitte, 2),
            round($scrimMitte * 0.8, 2),
            $scrimAussen,
        );
    }
}
