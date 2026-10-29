<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Branding Service Provider
 *
 * Manages site branding assets: exposes the ACF favicon on the frontend and
 * login screen, renders a custom login logo, and keeps ACF option values in
 * sync with the WordPress core custom_logo theme mod and site_icon option so
 * SEO plugins and social-sharing integrations use the correct images.
 */
class BrandingServiceProvider extends ServiceProvider
{
    /** Path of the per-site web manifest, relative to the site home. */
    public const MANIFEST_PATH = '/site.webmanifest';

    /** Browser chrome colour, same as the light theme-color meta in partials/header.blade.php. */
    public const MANIFEST_THEME_COLOR = '#F5F5F5';

    public function register(): void
    {
        // No bindings required
    }

    public function boot(): void
    {
        $this->addFaviconSupport();
        $this->addIconLinkSupport();
        $this->addManifestSupport();
        $this->addLoginLogoSupport();
        $this->syncAcfWithWordPress();
    }

    /**
     * Add favicon support using ACF options
     */
    private function addFaviconSupport(): void
    {
        // Site icon URLs: the ACF favicon wins over the core site icon, and a
        // sub-size in another format than the upload is replaced by the original.
        add_filter('get_site_icon_url', function (string $url, int $size, int $blogId): string {
            $faviconId = function_exists('get_field') ? (int) \WordpressStarter\Acf\Fields::option('site_favicon', 0) : 0;
            $iconId = $faviconId ?: (int) get_option('site_icon', 0);
            if (!$iconId) {
                return $url;
            }

            return self::iconUrl($iconId, $size) ?: $url;
        }, 10, 3);

        // Add favicon meta tags if ACF favicon is set
        add_action('wp_head', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');
            if (!$faviconId) {
                return;
            }

            // Don't output if WordPress already has a site icon
            if (has_site_icon()) {
                return;
            }

            // Order and attributes preserved from the original per-size blocks: 32, 16, 180 (apple-touch-icon), 192, 512.
            $faviconSizes = [
                32 => ['rel' => 'icon', 'type' => 'image/png'],
                16 => ['rel' => 'icon', 'type' => 'image/png'],
                180 => ['rel' => 'apple-touch-icon', 'type' => null],
                192 => ['rel' => 'icon', 'type' => 'image/png'],
                512 => ['rel' => 'icon', 'type' => 'image/png'],
            ];

            foreach ($faviconSizes as $size => $attrs) {
                $faviconUrl = self::iconUrl( (int) $faviconId, $size);
                if (!$faviconUrl) {
                    continue;
                }

                echo '<link rel="' . esc_attr($attrs['rel']) . '"'
                    . ( $attrs['type'] ? ' type="' . esc_attr($attrs['type']) . '"' : '' )
                    . ' sizes="' . absint($size) . 'x' . absint($size) . '" href="' . esc_url($faviconUrl) . '">' . "\n";
            }
        }, 1);
    }

    /**
     * Link tags WordPress' own site icon output does not cover: the SVG and
     * multi-size ICO favicon (ACF site_favicon_svg / site_favicon_ico) and the
     * web manifest. The PNG icons and apple-touch-icon stay with wp_site_icon().
     */
    private function addIconLinkSupport(): void
    {
        add_action('wp_head', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            $svgId = (int) \WordpressStarter\Acf\Fields::option('site_favicon_svg', 0);
            $icoId = (int) \WordpressStarter\Acf\Fields::option('site_favicon_ico', 0);
            $svg = $svgId ? wp_get_attachment_url($svgId) : false;
            $ico = $icoId ? wp_get_attachment_url($icoId) : false;
            $hasIcon = has_site_icon() || \WordpressStarter\Acf\Fields::option('site_favicon');

            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- every URL is passed through esc_url() in iconLinks()
            echo self::iconLinks(
                $svg ?: null,
                $ico ?: null,
                $hasIcon ? home_url(self::MANIFEST_PATH) : null
            );
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        }, 2);
    }

    /**
     * HTML for the extra icon links. Each argument is optional, an empty value
     * outputs nothing for that tag.
     */
    public static function iconLinks(?string $svgUrl, ?string $icoUrl, ?string $manifestUrl): string
    {
        $html = '';
        if ($icoUrl) {
            $html .= '<link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="' . esc_url($icoUrl) . '">' . "\n";
        }
        if ($svgUrl) {
            $html .= '<link rel="icon" type="image/svg+xml" sizes="any" href="' . esc_url($svgUrl) . '">' . "\n";
        }
        if ($manifestUrl) {
            $html .= '<link rel="manifest" href="' . esc_url($manifestUrl) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Serve /site.webmanifest per site (a JSON file cannot live in the media
     * library, and every blog of the network needs its own name and icons).
     */
    private function addManifestSupport(): void
    {
        add_action('template_redirect', function (): void {
            $requestUri = isset($_SERVER['REQUEST_URI'])
                ? sanitize_text_field(wp_unslash( (string) $_SERVER['REQUEST_URI']))
                : '';
            $homePath = (string) ( parse_url(home_url('/'), PHP_URL_PATH) ?: '/' );

            if (!self::isManifestRequest($requestUri, $homePath)) {
                return;
            }

            $iconId = (int) ( get_option('site_icon') ?: \WordpressStarter\Acf\Fields::option('site_favicon', 0) );
            $companyName = \WordpressStarter\Acf\Fields::option('company_name');
            $siteName = (string) get_bloginfo('name');

            $manifest = self::buildManifest(
                is_string($companyName) && $companyName !== '' ? $companyName : $siteName,
                $siteName,
                home_url('/'),
                $this->manifestIcons($iconId)
            );

            status_header(200);
            header('Content-Type: application/manifest+json; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
            echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }, 0);
    }

    /**
     * 192 and 512 px entries of the site icon, with the size of the file that
     * is really served (WordPress falls back to the full image when no
     * intermediate size exists, so the declared size must come from the file).
     *
     * @return array<int, array{src: string, sizes: string, type: string}>
     */
    private function manifestIcons(int $attachmentId): array
    {
        if ($attachmentId <= 0) {
            return [];
        }

        $icons = [];
        foreach ([192, 512] as $size) {
            $image = wp_get_attachment_image_src($attachmentId, [$size, $size]);
            if (!$image) {
                continue;
            }
            $icons[$image[0]] = [
                'src' => $image[0],
                'sizes' => $image[1] . 'x' . $image[2],
                // Type of the served file: sub-sizes of a PNG upload can be WebP.
                'type' => self::extension($image[0]) === 'webp' ? 'image/webp' : (string) ( get_post_mime_type($attachmentId) ?: 'image/png' ),
            ];
        }

        return array_values($icons);
    }

    /**
     * URL of an icon attachment for the given square size. Uploads are turned
     * into WebP sub-sizes (ImageServiceProvider), but iOS ignores WebP touch
     * icons and the link tags announce PNG. A sub-size whose format differs
     * from the original upload is therefore replaced by the original file,
     * which the browser scales down.
     */
    public static function iconUrl(int $attachmentId, int $size): ?string
    {
        // With the WebP conversion the main file is WebP as well; the uploaded
        // PNG then only lives on as original_image.
        $original = wp_get_original_image_url($attachmentId) ?: wp_get_attachment_url($attachmentId);
        $subSize = wp_get_attachment_image_url($attachmentId, [$size, $size]);

        if (!$subSize) {
            return $original ?: null;
        }

        if ($original && self::extension($subSize) !== self::extension($original)) {
            return $original;
        }

        return $subSize;
    }

    private static function extension(string $url): string
    {
        return strtolower(pathinfo( (string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    /**
     * True when the request path is this site's manifest (also under a
     * sub-directory home like /blog/), ignoring query string and case.
     */
    public static function isManifestRequest(string $requestUri, string $homePath): bool
    {
        $path = strtolower( (string) parse_url($requestUri, PHP_URL_PATH));

        return $path === strtolower(rtrim($homePath, '/') . self::MANIFEST_PATH);
    }

    /**
     * @param array<int, array{src: string, sizes: string, type: string}> $icons
     *
     * @return array<string, mixed>
     */
    public static function buildManifest(string $name, string $shortName, string $startUrl, array $icons): array
    {
        $manifest = [
            'name' => $name,
            'short_name' => $shortName !== '' ? $shortName : $name,
            'start_url' => $startUrl,
            'scope' => $startUrl,
            'display' => 'browser',
            'theme_color' => self::MANIFEST_THEME_COLOR,
            'background_color' => '#ffffff',
        ];

        if ($icons !== []) {
            $manifest['icons'] = $icons;
        }

        return $manifest;
    }

    /**
     * Add custom login page logo using ACF options
     */
    private function addLoginLogoSupport(): void
    {
        // Custom login logo
        add_action('login_enqueue_scripts', function (): void {
            $logoUrl = $this->getLogoUrl();
            if (!$logoUrl) {
                return;
            }
            // esc_url_raw() (not esc_url()) because this URL is inside a <style> block:
            // RAWTEXT does not decode HTML entities, so esc_url()'s "&#038;" would stay
            // literal and break the URL. Strip characters that could close the CSS
            // string/declaration early, including the single quote used as the
            // url('...') delimiter below and backslash escapes that could re-introduce one.
            $safeLogoUrl = str_replace(['"', "'", '\\', ')', ';', '}', "\n", "\r"], '', esc_url_raw($logoUrl));
            ?>
            <style type="text/css">
                #login h1 a, .login h1 a {
                    background-image: url('<?php echo $safeLogoUrl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already sanitized above via esc_url_raw() + char stripping for CSS-string safety ?>');
                    background-size: contain;
                    background-repeat: no-repeat;
                    background-position: center;
                    width: 100%;
                    height: 80px;
                }
            </style>
            <?php
        });

        // Custom login logo URL
        add_filter('login_headerurl', function (): string {
            return home_url();
        });

        // Custom login logo title
        add_filter('login_headertext', function (): string {
            return get_bloginfo('name');
        });
    }

    /**
     * Get logo URL from ACF options or Customizer
     */
    private function getLogoUrl(): ?string
    {
        return \WordpressStarter\Acf\Fields::siteLogoUrl();
    }

    /**
     * Sync ACF options with WordPress core settings
     *
     * When logo/favicon are set in ACF Theme Options, this syncs them to
     * WordPress core settings so SEO plugins, social sharing, and other
     * WordPress features use the correct images.
     */
    private function syncAcfWithWordPress(): void
    {
        // Sync on ACF options save
        add_action('acf/save_post', function ($postId): void {
            if ($postId !== 'options') {
                return;
            }

            $this->syncLogoToWordPress();
            $this->syncFaviconToWordPress();
        }, 20);

        // Also sync on init if values exist but aren't synced
        add_action('init', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            // Only sync if ACF values exist but WordPress values don't match
            $this->maybeInitialSync();
        }, 20);
    }

    /**
     * Sync ACF logo to WordPress custom_logo theme mod
     */
    private function syncLogoToWordPress(): void
    {
        if (!function_exists('get_field')) {
            return;
        }

        $acfLogo = get_field('site_logo', 'option');

        if ($acfLogo && !empty($acfLogo['ID'])) {
            // Set the WordPress custom_logo to the ACF logo
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }
    }

    /**
     * Sync ACF favicon to WordPress site_icon option
     */
    private function syncFaviconToWordPress(): void
    {
        if (!function_exists('get_field')) {
            return;
        }

        $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');

        if ($faviconId) {
            // Set the WordPress site_icon to the ACF favicon
            update_option('site_icon', $faviconId);
        }
    }

    /**
     * Initial sync if ACF values exist but WordPress values don't
     */
    private function maybeInitialSync(): void
    {
        // Only run once — bail if already synced in a previous request
        $transientKey = \WordpressStarter\ThemeContext::prefix() . '_initial_sync_done';
        if (get_transient($transientKey)) {
            return;
        }

        // Sync logo if ACF has one but WordPress doesn't
        $acfLogo = get_field('site_logo', 'option');
        $wpLogo = get_theme_mod('custom_logo');

        if ($acfLogo && !empty($acfLogo['ID']) && !$wpLogo) {
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }

        // Sync favicon if ACF has one but WordPress doesn't
        $acfFavicon = \WordpressStarter\Acf\Fields::option('site_favicon');
        $wpSiteIcon = get_option('site_icon');

        if ($acfFavicon && !$wpSiteIcon) {
            update_option('site_icon', $acfFavicon);
        }

        set_transient($transientKey, true, DAY_IN_SECONDS);
    }
}
