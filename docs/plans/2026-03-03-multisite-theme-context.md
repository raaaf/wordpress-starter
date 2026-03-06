# Multisite Theme Context Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Isolate all Theme-Option-Keys und content-generierende Aktionen pro Theme und Site, um Kollisionen in WP Multisite zu verhindern.

**Architecture:** Eine neue `ThemeContext`-Klasse liefert theme-spezifische Option-Keys (z.B. `moenius_content_setup_complete` statt `wp_starter_content_setup_complete`) und einen Site-Guard (`isActiveOnCurrentSite()`). Die `Application`-Klasse ruft beim ersten Boot eine einmalige Migration auf, die alte Keys in neue kopiert. Alle vier Themes bekommen dieselben Änderungen — nur der Slug unterscheidet sich (dynamisch aus `get_template()`).

**Tech Stack:** PHP 8.4, WordPress Multisite, PHPUnit, Composer

**Design-Dokument:** `docs/plans/2026-03-03-multisite-theme-context-design.md`

---

## Reihenfolge

Tasks 1–6: `wordpress-starter-theme` (Referenz-Implementierung)
Tasks 7–9: Dieselben Änderungen in `goldene-strategie`, `stiftungs-navigator`, `moenius`

---

## Task 1: `ThemeContext`-Klasse erstellen (wordpress-starter-theme)

**Files:**

- Create: `app/public/wp-content/themes/wordpress-starter-theme/src/ThemeContext.php`
- Create: `app/public/wp-content/themes/wordpress-starter-theme/tests/Unit/ThemeContextTest.php`

**Schritt 1: Test schreiben**

```php
<?php
// tests/Unit/ThemeContextTest.php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\ThemeContext;

final class ThemeContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_mock_template'] = 'wordpress-starter-theme';
        $GLOBALS['wp_mock_options'] = [];
        $GLOBALS['wp_mock_transients'] = [];
        ThemeContext::reset();
    }

    public function testSlugReturnsTemplateSlug(): void
    {
        $this->assertSame('wordpress-starter-theme', ThemeContext::slug());
    }

    public function testPrefixConvertsDashesToUnderscores(): void
    {
        $this->assertSame('wordpress_starter_theme', ThemeContext::prefix());
    }

    public function testOptionKeyPrefixesWithThemeSlug(): void
    {
        $this->assertSame(
            'wordpress_starter_theme_content_setup_complete',
            ThemeContext::optionKey('content_setup_complete')
        );
    }

    public function testIsActiveOnCurrentSiteReturnsTrueWhenTemplateMatches(): void
    {
        $this->assertTrue(ThemeContext::isActiveOnCurrentSite());
    }

    public function testIsActiveOnCurrentSiteReturnsFalseForDifferentTemplate(): void
    {
        $GLOBALS['wp_mock_template'] = 'other-theme';
        ThemeContext::reset();
        $this->assertFalse(ThemeContext::isActiveOnCurrentSite());
    }

    public function testMigrateCopiesOldKeysToNewKeys(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;
        $GLOBALS['wp_mock_options']['wp_starter_theme_activated'] = true;

        ThemeContext::migrate();

        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_content_setup_complete')
        );
        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_theme_activated')
        );
    }

    public function testMigrateDoesNotOverwriteExistingNewKey(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = 'old_value';
        $GLOBALS['wp_mock_options']['wordpress_starter_theme_content_setup_complete'] = 'new_value';

        ThemeContext::migrate();

        $this->assertSame(
            'new_value',
            get_option('wordpress_starter_theme_content_setup_complete')
        );
    }

    public function testMigrateIsIdempotent(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;

        ThemeContext::migrate();
        ThemeContext::migrate(); // zweimal aufrufen

        // Kein Fehler, Migration wurde nur einmal ausgeführt
        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_content_setup_complete')
        );
    }

    public function testMigrateSetsCompletionFlag(): void
    {
        ThemeContext::migrate();

        $this->assertTrue(
            (bool) get_option('wordpress_starter_theme_migration_done')
        );
    }

    public function testMigrateDoesNotDeleteOldKeys(): void
    {
        $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;

        ThemeContext::migrate();

        // Alter Key bleibt erhalten für Rollback-Sicherheit
        $this->assertTrue(
            (bool) get_option('wp_starter_content_setup_complete')
        );
    }
}
```

**Schritt 2: Test fehlschlagen lassen**

```bash
cd app/public/wp-content/themes/wordpress-starter-theme
composer test -- --filter ThemeContextTest
```

Erwartete Ausgabe: `Error: Class "WordpressStarter\ThemeContext" not found`

**Schritt 3: `ThemeContext` implementieren**

```php
<?php
// src/ThemeContext.php
declare(strict_types=1);

namespace WordpressStarter;

/**
 * Theme Context
 *
 * Zentrale Klasse für theme-spezifische Option-Keys und Site-Isolation
 * in WordPress Multisite. Alle Keys werden mit dem Theme-Slug geprefixed,
 * damit verschiedene Themes auf derselben Site nicht kollidieren.
 */
final class ThemeContext
{
    private static ?string $slug = null;

    /**
     * Theme-Slug aus get_template() — immer der Parent-Slug,
     * auch wenn ein WP-Child-Theme aktiv ist.
     */
    public static function slug(): string
    {
        if (self::$slug === null) {
            self::$slug = get_template();
        }
        return self::$slug;
    }

    /**
     * Prefix für Option-Keys: Bindestriche → Unterstriche.
     * 'wordpress-starter-theme' → 'wordpress_starter_theme'
     */
    public static function prefix(): string
    {
        return str_replace('-', '_', self::slug());
    }

    /**
     * Theme-spezifischer Option-Key.
     * ThemeContext::optionKey('content_setup_complete')
     * → 'wordpress_starter_theme_content_setup_complete'
     */
    public static function optionKey(string $key): string
    {
        return self::prefix() . '_' . $key;
    }

    /**
     * Prüft ob dieses Theme (oder ein Child davon) auf der aktuellen Site aktiv ist.
     * Schützt content-generierende admin_init-Handler vor Cross-Site-Ausführung
     * durch Super-Admins in WP Multisite.
     */
    public static function isActiveOnCurrentSite(): bool
    {
        return get_template() === self::slug();
    }

    /**
     * Einmalige Migration alter wp_starter_* Option-Keys → theme-spezifische Keys.
     * Läuft beim ersten boot() nach dem Theme-Update. Alte Keys bleiben erhalten.
     */
    public static function migrate(): void
    {
        $migrationKey = self::optionKey('migration_done');

        if (get_option($migrationKey)) {
            return;
        }

        $migrations = [
            'wp_starter_content_setup_complete'  => self::optionKey('content_setup_complete'),
            'wp_starter_setup_complete'          => self::optionKey('setup_complete'),
            'wp_starter_theme_activated'         => self::optionKey('theme_activated'),
            'wp_starter_welcome_dismissed'       => self::optionKey('welcome_dismissed'),
            'wp_starter_styleguide_page_id'      => self::optionKey('styleguide_page_id'),
            'wp_starter_styleguide_images'       => self::optionKey('styleguide_images'),
            'wp_starter_acf_prefill_pending'     => self::optionKey('acf_prefill_pending'),
            'wp_starter_dismissed_plugin_notice' => self::optionKey('dismissed_plugin_notice'),
        ];

        foreach ($migrations as $oldKey => $newKey) {
            $oldValue = get_option($oldKey);
            // Nur kopieren wenn alter Wert existiert und neuer Key noch nicht gesetzt
            if ($oldValue !== false && get_option($newKey) === false) {
                update_option($newKey, $oldValue);
            }
        }

        // Plugin-Configurator-Keys migrieren (dynamischer Suffix)
        $this->migratePluginConfiguratorKeys();

        update_option($migrationKey, true);
    }

    /**
     * Migriert wp_starter_configured_{slug} Keys für alle bekannten Plugin-Slugs.
     */
    private static function migratePluginConfiguratorKeys(): void
    {
        $pluginSlugs = [
            'wp-optimize',
            'wordpress-seo',
            'admin-site-enhancements',
            'ithemes-security',
            'webp-express',
            'contact-form-7',
        ];

        foreach ($pluginSlugs as $slug) {
            $oldKey = 'wp_starter_configured_' . $slug;
            $newKey = self::optionKey('configured_' . $slug);
            $oldValue = get_option($oldKey);
            if ($oldValue !== false && get_option($newKey) === false) {
                update_option($newKey, $oldValue);
            }
        }
    }

    /**
     * Setzt den gecachten Slug zurück — nur für Tests.
     * @internal
     */
    public static function reset(): void
    {
        self::$slug = null;
    }
}
```

**Wichtig:** Den Namespace an das jeweilige Theme anpassen:

- `wordpress-starter-theme` → `namespace WordpressStarter;`
- `goldene-strategie` → `namespace GoldeneStrategie;`
- `stiftungs-navigator` → `namespace StiftungsNavigator;`
- `moenius` → `namespace moenius;`

**Schritt 4: Test laufen lassen**

```bash
composer test -- --filter ThemeContextTest
```

Erwartete Ausgabe: `OK (9 tests, 9 assertions)`

**Schritt 5: `get_template()` und `delete_option()` zum Test-Bootstrap hinzufügen**

In `tests/bootstrap.php` prüfen ob folgende Stubs fehlen und ggf. ergänzen:

```php
if (!function_exists('get_template')) {
    function get_template(): string
    {
        return $GLOBALS['wp_mock_template'] ?? 'wordpress-starter-theme';
    }
}

if (!function_exists('get_stylesheet')) {
    function get_stylesheet(): string
    {
        return $GLOBALS['wp_mock_stylesheet'] ?? get_template();
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        unset($GLOBALS['wp_mock_options'][$option]);
        return true;
    }
}
```

**Schritt 6: Tests nochmal laufen lassen**

```bash
composer test -- --filter ThemeContextTest
```

Erwartete Ausgabe: `OK (9 tests, 9 assertions)`

**Schritt 7: Commit**

```bash
git add src/ThemeContext.php tests/Unit/ThemeContextTest.php tests/bootstrap.php
git commit -m "feat: add ThemeContext for multisite option key isolation"
```

---

## Task 2: `Application::boot()` — Migration einhängen (wordpress-starter-theme)

**Files:**

- Modify: `app/public/wp-content/themes/wordpress-starter-theme/src/Application.php`

**Schritt 1: Test schreiben**

In `tests/Unit/ApplicationTest.php` folgenden Test ergänzen:

```php
public function testBootRunsMigration(): void
{
    $GLOBALS['wp_mock_options']['wp_starter_content_setup_complete'] = true;
    $GLOBALS['wp_mock_template'] = 'wordpress-starter-theme';
    WordpressStarter\ThemeContext::reset();

    $app = Application::getInstance();
    $app->boot();

    $this->assertTrue(
        (bool) get_option('wordpress_starter_theme_migration_done')
    );
}
```

**Schritt 2: Test fehlschlagen lassen**

```bash
composer test -- --filter testBootRunsMigration
```

Erwartete Ausgabe: `FAIL — migration_done option not set`

**Schritt 3: Migration in `boot()` einhängen**

In `src/Application.php` die `boot()`-Methode anpassen:

```php
public function boot(): void
{
    // Migration läuft vor Provider-Registrierung, damit alle Provider
    // bereits die neuen Keys lesen können
    ThemeContext::migrate();

    // Phase 1: Instantiate and register all providers
    foreach ($this->providers as $providerClass) {
        $provider = $this->resolveProvider($providerClass);
        $provider->register();
    }

    // Phase 2: Boot all providers (after all are registered)
    foreach ($this->providers as $providerClass) {
        $provider = $this->resolveProvider($providerClass);
        $provider->boot();
    }
}
```

Außerdem den Import ergänzen (falls nicht schon vorhanden):

```php
use WordpressStarter\ThemeContext;
```

**Schritt 4: Test laufen lassen**

```bash
composer test -- --filter ApplicationTest
```

Erwartete Ausgabe: alle ApplicationTests grün

**Schritt 5: Commit**

```bash
git add src/Application.php tests/Unit/ApplicationTest.php
git commit -m "feat: run ThemeContext migration on boot"
```

---

## Task 3: `WelcomeServiceProvider` — Keys und Guards (wordpress-starter-theme)

**Files:**

- Modify: `app/public/wp-content/themes/wordpress-starter-theme/src/Providers/WelcomeServiceProvider.php`

**Schritt 1: Die 5 OPTION\_\*-Konstanten ersetzen**

Die bisherigen Konstanten (Zeilen 15–19):

```php
private const OPTION_ACTIVATED        = 'wp_starter_theme_activated';
private const OPTION_DISMISSED        = 'wp_starter_welcome_dismissed';
private const OPTION_PAGE_ID          = 'wp_starter_styleguide_page_id';
private const OPTION_IMAGES           = 'wp_starter_styleguide_images';
private const OPTION_ACF_PREFILL_PENDING = 'wp_starter_acf_prefill_pending';
```

Ersetzen durch dynamische Methoden (Konstanten entfernen, stattdessen):

```php
private static function optActivated(): string        { return ThemeContext::optionKey('theme_activated'); }
private static function optDismissed(): string        { return ThemeContext::optionKey('welcome_dismissed'); }
private static function optPageId(): string           { return ThemeContext::optionKey('styleguide_page_id'); }
private static function optImages(): string           { return ThemeContext::optionKey('styleguide_images'); }
private static function optAcfPrefillPending(): string { return ThemeContext::optionKey('acf_prefill_pending'); }
```

Dann alle `self::OPTION_*`-Referenzen im Provider ersetzen:

- `self::OPTION_ACTIVATED` → `self::optActivated()`
- `self::OPTION_DISMISSED` → `self::optDismissed()`
- `self::OPTION_PAGE_ID` → `self::optPageId()`
- `self::OPTION_IMAGES` → `self::optImages()`
- `self::OPTION_ACF_PREFILL_PENDING` → `self::optAcfPrefillPending()`

**Schritt 2: Hardcodierte Keys ersetzen**

Zeile 159:

```php
// Alt:
$setupComplete = get_option('wp_starter_setup_complete') || get_option('wp_starter_content_setup_complete');

// Neu:
$setupComplete = get_option(ThemeContext::optionKey('setup_complete'))
              || get_option(ThemeContext::optionKey('content_setup_complete'));
```

**Schritt 3: Guard in `maybePrefillAcfOptions()` einbauen**

```php
public function maybePrefillAcfOptions(): void
{
    // Guard: nur ausführen wenn dieses Theme auf der aktuellen Site aktiv ist
    if (!ThemeContext::isActiveOnCurrentSite()) {
        return;
    }

    if (!get_option(self::optAcfPrefillPending())) {
        return;
    }

    $this->prefillAcfOptions();
}
```

**Schritt 4: Guard in `onThemeActivation()` einbauen**

```php
public function onThemeActivation(): void
{
    if (!ThemeContext::isActiveOnCurrentSite()) {
        return;
    }

    update_option(self::optActivated(), true);
    delete_option(self::optDismissed());
    // ... Rest unverändert
}
```

**Schritt 5: Import ergänzen**

Am Anfang der Datei:

```php
use WordpressStarter\ThemeContext;
```

**Schritt 6: Tests laufen lassen**

```bash
composer test
```

Erwartete Ausgabe: alle Tests grün (keine neuen Tests nötig — ThemeContext ist bereits getestet)

**Schritt 7: Commit**

```bash
git add src/Providers/WelcomeServiceProvider.php
git commit -m "feat: use ThemeContext keys and guards in WelcomeServiceProvider"
```

---

## Task 4: `PluginServiceProvider` — Keys und Guards (wordpress-starter-theme)

**Files:**

- Modify: `app/public/wp-content/themes/wordpress-starter-theme/src/Providers/PluginServiceProvider.php`

**Schritt 1: Alle `wp_starter_*`-Keys ersetzen**

| Alt                                            | Neu                                                  |
| ---------------------------------------------- | ---------------------------------------------------- |
| `'wp_starter_content_setup_complete'`          | `ThemeContext::optionKey('content_setup_complete')`  |
| `'wp_starter_styleguide_images'`               | `ThemeContext::optionKey('styleguide_images')`       |
| `'wp_starter_styleguide_page_id'`              | `ThemeContext::optionKey('styleguide_page_id')`      |
| `'wp_starter_welcome_dismissed'`               | `ThemeContext::optionKey('welcome_dismissed')`       |
| `'wp_starter_dismissed_plugin_notice'`         | `ThemeContext::optionKey('dismissed_plugin_notice')` |
| `'wp_starter_activation_redirect'` (Transient) | `ThemeContext::optionKey('activation_redirect')`     |

Betroffene Zeilen: 103, 145, 245, 248, 270, 462, 510, 512, 816, 824, 828, 846, 1364, 1375

**Schritt 2: Guards in die 4 content-generierenden Methoden einbauen**

`runContentSetup()` (ab Zeile 100):

```php
public function runContentSetup(): void
{
    if (!ThemeContext::isActiveOnCurrentSite()) {
        return;
    }
    // ... Rest unverändert
}
```

`handleRerunContentSetup()` (ab Zeile 205):

```php
public function handleRerunContentSetup(): void
{
    if (!ThemeContext::isActiveOnCurrentSite()) {
        return;
    }
    // ... Rest unverändert
}
```

`handleGenerateDemoPosts()` (ab Zeile 280):

```php
public function handleGenerateDemoPosts(): void
{
    if (!ThemeContext::isActiveOnCurrentSite()) {
        return;
    }
    // ... Rest unverändert
}
```

`handleDeleteDemoPosts()` (ab Zeile 372):

```php
public function handleDeleteDemoPosts(): void
{
    if (!ThemeContext::isActiveOnCurrentSite()) {
        return;
    }
    // ... Rest unverändert
}
```

**Schritt 3: Import ergänzen**

```php
use WordpressStarter\ThemeContext;
```

**Schritt 4: Tests laufen lassen**

```bash
composer test
```

Erwartete Ausgabe: alle Tests grün

**Schritt 5: Commit**

```bash
git add src/Providers/PluginServiceProvider.php
git commit -m "feat: use ThemeContext keys and guards in PluginServiceProvider"
```

---

## Task 5: `AbstractPluginConfigurator` — Keys (wordpress-starter-theme)

**Files:**

- Modify: `app/public/wp-content/themes/wordpress-starter-theme/src/PluginConfigurators/AbstractPluginConfigurator.php`

**Schritt 1: Drei Zeilen ersetzen**

Zeile 74:

```php
// Alt:
update_option('wp_starter_configured_' . static::getPluginSlug(), true);
// Neu:
update_option(ThemeContext::optionKey('configured_' . static::getPluginSlug()), true);
```

Zeile 82:

```php
// Alt:
return (bool) get_option('wp_starter_configured_' . static::getPluginSlug(), false);
// Neu:
return (bool) get_option(ThemeContext::optionKey('configured_' . static::getPluginSlug()), false);
```

Zeile 92:

```php
// Alt:
delete_option('wp_starter_configured_' . static::getPluginSlug());
// Neu:
delete_option(ThemeContext::optionKey('configured_' . static::getPluginSlug()));
```

**Schritt 2: Import ergänzen**

```php
use WordpressStarter\ThemeContext;
```

**Schritt 3: Tests laufen lassen**

```bash
composer test
```

**Schritt 4: Commit**

```bash
git add src/PluginConfigurators/AbstractPluginConfigurator.php
git commit -m "feat: use ThemeContext keys in AbstractPluginConfigurator"
```

---

## Task 6: `Acf/Options.php` — Key (wordpress-starter-theme)

**Files:**

- Modify: `app/public/wp-content/themes/wordpress-starter-theme/src/Acf/Options.php`

**Schritt 1: Zeile 748 ersetzen**

```php
// Alt:
$contentSetupComplete = get_option('wp_starter_content_setup_complete');
// Neu:
$contentSetupComplete = get_option(ThemeContext::optionKey('content_setup_complete'));
```

**Schritt 2: Import ergänzen**

```php
use WordpressStarter\ThemeContext;
```

**Schritt 3: Alle Tests laufen lassen**

```bash
composer test
```

Erwartete Ausgabe: alle Tests grün, keine Regressionen

**Schritt 4: Commit**

```bash
git add src/Acf/Options.php
git commit -m "feat: use ThemeContext key in Acf/Options"
```

---

## Task 7: Alle Änderungen nach `goldene-strategie` übertragen

**Files:**

- Create: `app/public/wp-content/themes/goldene-strategie/src/ThemeContext.php`
- Modify: `app/public/wp-content/themes/goldene-strategie/src/Application.php`
- Modify: `app/public/wp-content/themes/goldene-strategie/src/Providers/WelcomeServiceProvider.php`
- Modify: `app/public/wp-content/themes/goldene-strategie/src/Providers/PluginServiceProvider.php`
- Modify: `app/public/wp-content/themes/goldene-strategie/src/PluginConfigurators/AbstractPluginConfigurator.php`
- Modify: `app/public/wp-content/themes/goldene-strategie/src/Acf/Options.php`
- Modify: `app/public/wp-content/themes/goldene-strategie/tests/bootstrap.php`

**Schritt 1: `ThemeContext.php` kopieren und Namespace anpassen**

Datei aus `wordpress-starter-theme/src/ThemeContext.php` kopieren.
Namespace ändern:

```php
namespace GoldeneStrategie;
```

Die `migratePluginConfiguratorKeys()`-Methode: Plugin-Slugs anpassen falls `goldene-strategie` andere Plugins nutzt (in `composer.json` prüfen).

**Schritt 2: Tests kopieren**

`tests/Unit/ThemeContextTest.php` aus dem Starter kopieren.
Namespace und `wp_mock_template` anpassen:

```php
namespace Tests\Unit;
use GoldeneStrategie\ThemeContext;
// ...
$GLOBALS['wp_mock_template'] = 'goldene-strategie';
// Erwartete Werte:
$this->assertSame('goldene-strategie', ThemeContext::slug());
$this->assertSame('goldene_strategie', ThemeContext::prefix());
$this->assertSame('goldene_strategie_content_setup_complete', ThemeContext::optionKey('content_setup_complete'));
```

**Schritt 3: Tests laufen lassen**

```bash
cd app/public/wp-content/themes/goldene-strategie
composer test -- --filter ThemeContextTest
```

Erwartete Ausgabe: `OK`

**Schritt 4: Application, Provider, Configurator, Options wie in Tasks 2–6 anpassen**

Exakt dieselben Änderungen wie beim Starter — nur `WordpressStarter\ThemeContext` → `GoldeneStrategie\ThemeContext`.

**Schritt 5: Alle Tests laufen lassen**

```bash
composer test
```

**Schritt 6: Commit**

```bash
git add src/ThemeContext.php src/Application.php src/Providers/WelcomeServiceProvider.php \
        src/Providers/PluginServiceProvider.php src/PluginConfigurators/AbstractPluginConfigurator.php \
        src/Acf/Options.php tests/
git commit -m "feat: add ThemeContext multisite isolation to goldene-strategie"
```

---

## Task 8: Alle Änderungen nach `stiftungs-navigator` übertragen

Exakt wie Task 7, mit folgenden Anpassungen:

- Namespace: `StiftungsNavigator`
- `wp_mock_template`: `'stiftungs-navigator'`
- Erwarteter Prefix: `'stiftungs_navigator'`
- Erwarteter Key: `'stiftungs_navigator_content_setup_complete'`

```bash
cd app/public/wp-content/themes/stiftungs-navigator
composer test
git commit -m "feat: add ThemeContext multisite isolation to stiftungs-navigator"
```

---

## Task 9: Alle Änderungen nach `moenius` übertragen

Exakt wie Task 7, mit folgenden Anpassungen:

- Namespace: `moenius`
- `wp_mock_template`: `'moenius'`
- Erwarteter Prefix: `'moenius'` (keine Bindestriche)
- Erwarteter Key: `'moenius_content_setup_complete'`

`migratePluginConfiguratorKeys()` prüfen — `moenius` hat `MemberArea` und andere spezifische Features, keine zusätzlichen Plugin-Slugs nötig.

```bash
cd app/public/wp-content/themes/moenius
composer test
git commit -m "feat: add ThemeContext multisite isolation to moenius"
```

---

## Task 10: Verifikation

**Schritt 1: Alle vier Themes testen**

```bash
cd app/public/wp-content/themes/wordpress-starter-theme && composer test
cd app/public/wp-content/themes/goldene-strategie && composer test
cd app/public/wp-content/themes/stiftungs-navigator && composer test
cd app/public/wp-content/themes/moenius && composer test
```

Alle grün.

**Schritt 2: Manuelle Verifikation im Local-Admin**

1. Local-Site starten
2. Im WordPress-Admin: Theme wechseln → prüfen ob Welcome-Notice erscheint
3. Theme-Einstellungen → Tools → "Content neu generieren" → prüfen ob nur die aktuelle Site betroffen ist
4. In der DB prüfen: `SELECT option_name, option_value FROM wp_options WHERE option_name LIKE 'moenius_%';` → neue Keys vorhanden

**Schritt 3: Sicherstellen dass alte Keys noch da sind**

```sql
SELECT option_name FROM wp_options WHERE option_name LIKE 'wp_starter_%';
```

Alte Keys müssen noch vorhanden sein (nicht gelöscht).

**Schritt 4: CLAUDE.md und README prüfen**

Nichts zu aktualisieren — diese Änderung ist intern und betrifft keine öffentliche API.
