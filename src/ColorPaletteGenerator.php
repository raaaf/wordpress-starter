<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Generates color palettes from a base color
 *
 * Creates a full range of color shades (50-950) from a single
 * base color (500), similar to Tailwind CSS color palettes.
 */
class ColorPaletteGenerator
{
    /**
     * Shade levels with their lightness adjustments
     * Positive = lighter, Negative = darker
     *
     * @var array<int, float>
     */
    private const SHADE_ADJUSTMENTS = [
        50 => 0.95,   // Very light
        100 => 0.90,
        200 => 0.80,
        300 => 0.65,
        400 => 0.45,
        500 => 0.0,   // Base color
        600 => -0.15,
        700 => -0.30,
        800 => -0.45,
        900 => -0.60,
        950 => -0.75, // Very dark
    ];

    /**
     * Generate a complete color palette from a base color
     *
     * @param string $hexColor Base color in hex format (#RRGGBB)
     * @return array<int, string> Array of shade => hex color
     */
    public function generate(string $hexColor): array
    {
        $rgb = $this->hexToRgb($hexColor);
        if ($rgb === null) {
            return [];
        }

        $hsl = $this->rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
        $palette = [];

        foreach (self::SHADE_ADJUSTMENTS as $shade => $adjustment) {
            if ($shade === 500) {
                // Base color unchanged
                $palette[$shade] = strtoupper($hexColor);
            } else {
                // Adjust lightness
                $newLightness = $this->adjustLightness($hsl['l'], $adjustment);
                $newRgb = $this->hslToRgb($hsl['h'], $hsl['s'], $newLightness);
                $palette[$shade] = $this->rgbToHex($newRgb['r'], $newRgb['g'], $newRgb['b']);
            }
        }

        ksort($palette);
        return $palette;
    }

    /**
     * Adjust lightness value based on adjustment factor
     *
     * @param float $lightness Current lightness (0-1)
     * @param float $adjustment Adjustment factor
     * @return float New lightness value (0-1)
     */
    private function adjustLightness(float $lightness, float $adjustment): float
    {
        if ($adjustment > 0) {
            // Lighter: move towards 1.0 (white)
            return $lightness + ( 1.0 - $lightness ) * $adjustment;
        } else {
            // Darker: move towards 0.0 (black)
            return $lightness * ( 1.0 + $adjustment );
        }
    }

    /**
     * Convert hex color to RGB
     *
     * @param string $hex Hex color (#RGB or #RRGGBB)
     * @return array{r: int, g: int, b: int}|null
     */
    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');

        // Support 3-character hex
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return null;
        }

        $rgb = sscanf($hex, '%02x%02x%02x');
        if ($rgb === null || count($rgb) !== 3) {
            return null;
        }

        return [
            'r' => (int) $rgb[0],
            'g' => (int) $rgb[1],
            'b' => (int) $rgb[2],
        ];
    }

    /**
     * Convert RGB to hex color
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return string Hex color (#RRGGBB)
     */
    private function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf(
            '#%02X%02X%02X',
            max(0, min(255, $r)),
            max(0, min(255, $g)),
            max(0, min(255, $b))
        );
    }

    /**
     * Convert RGB to HSL
     *
     * @param int $r Red (0-255)
     * @param int $g Green (0-255)
     * @param int $b Blue (0-255)
     * @return array{h: float, s: float, l: float}
     */
    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ( $max + $min ) / 2;
        $h = 0.0;
        $s = 0.0;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );

            switch ($max) {
                case $r:
                    $h = ( ( $g - $b ) / $d + ( $g < $b ? 6 : 0 ) ) / 6;
                    break;
                case $g:
                    $h = ( ( $b - $r ) / $d + 2 ) / 6;
                    break;
                case $b:
                    $h = ( ( $r - $g ) / $d + 4 ) / 6;
                    break;
            }
        }

        return ['h' => $h, 's' => $s, 'l' => $l];
    }

    /**
     * Convert HSL to RGB
     *
     * @param float $h Hue (0-1)
     * @param float $s Saturation (0-1)
     * @param float $l Lightness (0-1)
     * @return array{r: int, g: int, b: int}
     */
    private function hslToRgb(float $h, float $s, float $l): array
    {
        if ($s === 0.0) {
            $r = $l;
            $g = $l;
            $b = $l;
        } else {
            $q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hueToRgb($p, $q, $h + 1 / 3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1 / 3);
        }

        return [
            'r' => (int) round($r * 255),
            'g' => (int) round($g * 255),
            'b' => (int) round($b * 255),
        ];
    }

    /**
     * Helper function for HSL to RGB conversion
     *
     * @param float $p
     * @param float $q
     * @param float $t
     * @return float
     */
    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            ++$t;
        }
        if ($t > 1) {
            --$t;
        }
        if ($t < 1 / 6) {
            return $p + ( $q - $p ) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
        }
        return $p;
    }

    /**
     * Convert a palette to Figma token format
     *
     * @param array<int, string> $palette Shade => hex color array
     * @param string $colorName Base name for the color (e.g., 'accent', 'primary')
     * @return array<string, array<string, mixed>> Figma-compatible token structure
     */
    public function toFigmaTokenFormat(array $palette, string $colorName): array
    {
        $tokens = [];

        foreach ($palette as $shade => $hex) {
            $rgb = $this->hexToRgb($hex);
            if ($rgb === null) {
                continue;
            }

            $tokens[ (string) $shade] = [
                '$type' => 'color',
                '$value' => [
                    'colorSpace' => 'srgb',
                    'components' => [
                        $rgb['r'] / 255,
                        $rgb['g'] / 255,
                        $rgb['b'] / 255,
                    ],
                    'alpha' => 1,
                    'hex' => $hex,
                ],
                '$extensions' => [
                    'com.figma.variableId' => 'generated-' . $colorName . '-' . $shade,
                ],
            ];
        }

        return $tokens;
    }
}
