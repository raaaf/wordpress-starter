<?php

declare(strict_types=1);

namespace WordpressStarter\Content;

/**
 * Data for the design-system reference on the styleguide page.
 *
 * Only names live here, never markup and never colour values. The swatches are
 * rendered with `var(--token)` so what you see on the page is whatever the token
 * currently resolves to — rename or drop a token and its swatch goes blank, which
 * is the point. The predecessor of this file hard-coded the whole reference as an
 * HTML string, so it kept showing a design system the theme no longer had.
 *
 * @see templates/styleguide/tokens.blade.php
 */
final class StyleguideReference
{
    private function __construct()
    {
    }

    /**
     * Type scale. Key is the utility class, value the label shown next to it.
     *
     * @return array<string, string>
     */
    public static function typeScale(): array
    {
        return [
            'text-display' => 'Display',
            'text-h1' => 'Heading 1',
            'text-h2' => 'Heading 2',
            'text-h3' => 'Heading 3',
            'text-h4' => 'Heading 4',
            'text-h5' => 'Heading 5',
            'text-body-large' => 'Body Large',
            'text-body' => 'Body',
            'text-body-small' => 'Body Small',
            'text-caption' => 'Caption',
            'text-overline' => 'Overline',
            'text-code' => 'Code',
        ];
    }

    /**
     * Surface tokens, rendered as filled swatches.
     *
     * `textRole` names the CSS custom property the sample text should use on
     * that surface. `--bg-brand` stays fixed across both colour schemes
     * (accent-500 in both) and pairs with the fixed text role
     * `--text-on-brand`. `--bg-accent` and `--bg-inverse` flip with the
     * colour scheme and pair with `--text-inverse`, which flips too.
     * Omitted means the default `--text-primary` is readable on that
     * surface already.
     *
     * @return array<int, array{token: string, label: string, textRole?: string}>
     */
    public static function surfaces(): array
    {
        return [
            ['token' => '--bg-primary', 'label' => 'surface'],
            ['token' => '--bg-secondary', 'label' => 'surface-secondary'],
            ['token' => '--bg-tertiary', 'label' => 'surface-tertiary'],
            ['token' => '--bg-brand', 'label' => 'surface-brand', 'textRole' => '--text-on-brand'],
            ['token' => '--bg-brand-subtle', 'label' => 'surface-brand-subtle'],
            ['token' => '--bg-accent', 'label' => 'surface-accent', 'textRole' => '--text-inverse'],
            ['token' => '--bg-accent-subtle', 'label' => 'surface-accent-subtle'],
            ['token' => '--bg-inverse', 'label' => 'surface-inverse', 'textRole' => '--text-inverse'],
            ['token' => '--bg-success', 'label' => 'surface-success'],
            ['token' => '--bg-warning', 'label' => 'surface-warning'],
            ['token' => '--bg-error', 'label' => 'surface-error'],
            ['token' => '--bg-disabled', 'label' => 'surface-disabled'],
        ];
    }

    /**
     * Text roles, rendered as coloured sample text on the page surface.
     *
     * @return array<string, string>
     */
    public static function textRoles(): array
    {
        return [
            '--text-primary' => 'content',
            '--text-secondary' => 'content-secondary',
            '--text-tertiary' => 'content-tertiary',
            '--text-brand' => 'content-brand',
            '--text-accent' => 'content-accent',
            '--text-link' => 'content-link',
            '--text-success' => 'content-success',
            '--text-warning' => 'content-warning',
            '--text-error' => 'content-error',
            '--text-disabled' => 'content-disabled',
        ];
    }

    /**
     * Border roles, rendered as a bordered box.
     *
     * @return array<string, string>
     */
    public static function borderRoles(): array
    {
        return [
            '--border-default' => 'line',
            '--border-subtle' => 'line-subtle',
            '--border-strong' => 'line-strong',
            '--border-control' => 'line-control',
            '--border-brand' => 'line-brand',
            '--border-focus' => 'line-focus',
            '--border-error' => 'line-error',
        ];
    }

    /**
     * Shadow tokens. Both spellings are shown deliberately: the plain Tailwind
     * step and the theme's own component shadow, because they used to disagree.
     *
     * @return array<string, string>
     */
    public static function shadows(): array
    {
        return [
            '--shadow-sm' => 'shadow-sm',
            '--shadow-md' => 'shadow-md',
            '--shadow-lg' => 'shadow-lg',
            '--shadow-xl' => 'shadow-xl',
            '--shadow-button' => 'shadow-button',
            '--shadow-card' => 'shadow-card',
            '--shadow-card-hover' => 'shadow-card-hover',
            '--shadow-input' => 'shadow-input',
            '--shadow-dropdown' => 'shadow-dropdown',
            '--shadow-modal' => 'shadow-modal',
            '--shadow-focus-ring' => 'shadow-focus-ring',
        ];
    }

    /**
     * Gradient stop pairs used by the button and accent fills.
     *
     * @return array<int, array{start: string, end: string, label: string}>
     */
    public static function gradients(): array
    {
        return [
            [
                'start' => '--gradient-primary-start',
                'end' => '--gradient-primary-end',
                'label' => 'primary',
            ],
            [
                'start' => '--gradient-primary-hover-start',
                'end' => '--gradient-primary-hover-end',
                'label' => 'primary hover',
            ],
            [
                'start' => '--gradient-primary-active-start',
                'end' => '--gradient-primary-active-end',
                'label' => 'primary active',
            ],
            [
                'start' => '--gradient-subtle-start',
                'end' => '--gradient-subtle-end',
                'label' => 'subtle',
            ],
            [
                'start' => '--gradient-dark-start',
                'end' => '--gradient-dark-end',
                'label' => 'dark',
            ],
        ];
    }

    /**
     * Spacing steps, rendered as bars.
     *
     * @return array<int, string>
     */
    public static function spacing(): array
    {
        return ['--spacing-1', '--spacing-2', '--spacing-3', '--spacing-4', '--spacing-5', '--spacing-6', '--spacing-8', '--spacing-10', '--spacing-12', '--spacing-16', '--spacing-20', '--spacing-24'];
    }

    /**
     * Radius steps, rendered as rounded boxes.
     *
     * @return array<string, string>
     */
    public static function radii(): array
    {
        return [
            '--radius-sm' => 'radius-sm',
            '--radius-md' => 'radius-md',
            '--radius-lg' => 'radius-lg',
            '--radius-xl' => 'radius-xl',
            '--card-radius' => 'card-radius',
            '--radius-full' => 'radius-full',
        ];
    }
}
